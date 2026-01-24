<?php

namespace App\Services;

use App\Enums\BrowserName;
use App\Enums\ConditionOperator;
use App\Enums\ConditionType;
use App\Enums\DeviceType;
use App\Enums\LinkType;
use App\Enums\OperatingSystem;
use App\Models\Link;
use App\Models\LinkRule;
use App\Models\LinkRuleCondition;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class LinkRedirectResolver
{
    public function __construct(private IpCountryResolver $ipCountryResolver) {}

    /**
     * @return array{destination_url: string, rule_id: int|null}
     */
    public function resolveWithRule(Link $link, Request $request): array
    {
        if ($link->link_type !== LinkType::Dynamic) {
            return [
                'destination_url' => $link->destination_url,
                'rule_id' => null,
            ];
        }

        $context = $this->buildContext($request);

        return $this->resolveDynamic($link, $context);
    }

    public function resolve(Link $link, Request $request): string
    {
        return $this->resolveWithRule($link, $request)['destination_url'];
    }

    /**
     * @return array{
     *     country: string|null,
     *     device_type: string|null,
     *     operating_system: string|null,
     *     browser: string|null,
     *     referrer_host: string|null,
     *     referrer_path: string|null,
     *     utm_source: string|null,
     *     utm_medium: string|null,
     *     utm_campaign: string|null,
     *     language: string|null,
     *     timestamp: \Carbon\CarbonImmutable
     * }
     */
    private function buildContext(Request $request): array
    {
        $userAgent = $request->userAgent();
        $referrer = $request->headers->get('referer');
        $referrerHost = null;
        $referrerPath = null;

        if (is_string($referrer) && $referrer !== '') {
            $referrerHost = $this->normalizeString(parse_url($referrer, PHP_URL_HOST));
            $referrerPath = $this->normalizeString(parse_url($referrer, PHP_URL_PATH));
        }

        return [
            'country' => $this->countryCode($request),
            'device_type' => $this->deviceType($userAgent),
            'operating_system' => $this->operatingSystem($userAgent),
            'browser' => $this->browserName($userAgent),
            'referrer_host' => $referrerHost,
            'referrer_path' => $referrerPath,
            'utm_source' => $this->normalizeString($request->query('utm_source')),
            'utm_medium' => $this->normalizeString($request->query('utm_medium')),
            'utm_campaign' => $this->normalizeString($request->query('utm_campaign')),
            'language' => $this->acceptLanguage($request->header('accept-language')),
            'timestamp' => CarbonImmutable::now(),
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{destination_url: string, rule_id: int|null}
     */
    private function resolveDynamic(Link $link, array $context): array
    {
        $fallback = $link->fallback_destination_url ?? $link->destination_url;
        $maxRules = (int) config('links.dynamic.max_rules');
        $maxConditionsPerRule = (int) config('links.dynamic.max_conditions_per_rule');
        $maxTotalConditions = (int) config('links.dynamic.max_total_conditions');

        $rules = $this->loadRules($link, $maxRules, $maxConditionsPerRule);

        $evaluatedConditions = 0;

        foreach ($rules as $rule) {
            $conditions = $rule['conditions'] ?? [];

            if ($conditions === []) {
                continue;
            }

            $evaluatedConditions += count($conditions);

            if ($evaluatedConditions > $maxTotalConditions) {
                break;
            }

            if ($this->ruleMatches($conditions, $context)) {
                return [
                    'destination_url' => $rule['destination_url'],
                    'rule_id' => $rule['id'],
                ];
            }
        }

        return [
            'destination_url' => $fallback,
            'rule_id' => null,
        ];
    }

    /**
     * @param  array<int, array{condition_type: string|null, operator: string|null, value: mixed}>  $conditions
     * @param  array<string, mixed>  $context
     */
    private function ruleMatches(array $conditions, array $context): bool
    {
        foreach ($conditions as $condition) {
            if (! $this->conditionMatches($condition, $context)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array{condition_type: string|null, operator: string|null, value: mixed}  $condition
     * @param  array<string, mixed>  $context
     */
    private function conditionMatches(array $condition, array $context): bool
    {
        $type = ConditionType::tryFrom($condition['condition_type'] ?? '');
        $operator = ConditionOperator::tryFrom($condition['operator'] ?? '');
        $value = $condition['value'] ?? null;

        if (! $type || ! $operator) {
            return false;
        }

        return match ($type) {
            ConditionType::Country => $this->evaluateString($context['country'] ?? null, $operator, $value, true),
            ConditionType::DeviceType => $this->evaluateString($context['device_type'] ?? null, $operator, $value),
            ConditionType::OperatingSystem => $this->evaluateString($context['operating_system'] ?? null, $operator, $value),
            ConditionType::Browser => $this->evaluateString($context['browser'] ?? null, $operator, $value),
            ConditionType::ReferrerDomain => $this->evaluateString($context['referrer_host'] ?? null, $operator, $value),
            ConditionType::ReferrerPath => $this->evaluateString($context['referrer_path'] ?? null, $operator, $value),
            ConditionType::UtmSource => $this->evaluateString($context['utm_source'] ?? null, $operator, $value),
            ConditionType::UtmMedium => $this->evaluateString($context['utm_medium'] ?? null, $operator, $value),
            ConditionType::UtmCampaign => $this->evaluateString($context['utm_campaign'] ?? null, $operator, $value),
            ConditionType::Language => $this->evaluateString($context['language'] ?? null, $operator, $value),
            ConditionType::TimeWindow => $this->evaluateTimeWindow($operator, $value),
        };
    }

    /**
     * @return array<int, array{id: int, destination_url: string, conditions: array<int, array{condition_type: string|null, operator: string|null, value: mixed}>}>
     */
    private function loadRules(Link $link, int $maxRules, int $maxConditionsPerRule): array
    {
        $enabled = (bool) config('links.dynamic.cache.enabled', false);
        $ttlSeconds = (int) config('links.dynamic.cache.ttl_seconds', 0);
        $resolver = fn () => $this->queryRules($link, $maxRules, $maxConditionsPerRule);

        if (! $enabled || $ttlSeconds <= 0) {
            return $resolver();
        }

        $cacheKey = $this->ruleCacheKey($link, $maxRules, $maxConditionsPerRule);

        return Cache::remember($cacheKey, now()->addSeconds($ttlSeconds), $resolver);
    }

    private function ruleCacheKey(Link $link, int $maxRules, int $maxConditionsPerRule): string
    {
        $timestamp = $link->updated_at?->getTimestamp() ?? 0;

        return sprintf(
            'links.dynamic.rules.%s.%s.%s.%s',
            $link->id,
            $timestamp,
            $maxRules,
            $maxConditionsPerRule,
        );
    }

    /**
     * @return array<int, array{id: int, destination_url: string, conditions: array<int, array{condition_type: string|null, operator: string|null, value: mixed}>}>
     */
    private function queryRules(Link $link, int $maxRules, int $maxConditionsPerRule): array
    {
        return $link->rules()
            ->where('enabled', true)
            ->orderBy('priority')
            ->limit($maxRules)
            ->with('conditions')
            ->get()
            ->map(fn (LinkRule $rule) => [
                'id' => $rule->id,
                'destination_url' => $rule->destination_url,
                'conditions' => $rule->conditions
                    ->take($maxConditionsPerRule)
                    ->map(fn (LinkRuleCondition $condition) => [
                        'condition_type' => $condition->condition_type?->value ?? null,
                        'operator' => $condition->operator?->value ?? null,
                        'value' => $condition->value,
                    ])
                    ->all(),
            ])
            ->all();
    }

    private function evaluateTimeWindow(ConditionOperator $operator, mixed $value): bool
    {
        if ($operator !== ConditionOperator::Equals) {
            return false;
        }

        if (! is_array($value)) {
            return false;
        }

        $timezone = $value['timezone'] ?? null;

        if (! is_string($timezone) || $timezone === '') {
            return false;
        }

        $now = CarbonImmutable::now($timezone);

        $days = $value['days'] ?? null;

        if (is_array($days) && $days !== []) {
            $normalizedDays = array_map($this->normalizeString(...), $days);

            if (! in_array(Str::lower($now->format('l')), $normalizedDays, true)) {
                return false;
            }
        }

        $hours = $value['hours'] ?? null;

        if (is_array($hours)) {
            $start = $hours['start'] ?? null;
            $end = $hours['end'] ?? null;

            if (! is_int($start) || ! is_int($end)) {
                return false;
            }

            $hour = (int) $now->format('G');

            if ($start <= $end) {
                if ($hour < $start || $hour > $end) {
                    return false;
                }
            } else {
                if ($hour < $start && $hour > $end) {
                    return false;
                }
            }
        }

        return true;
    }

    private function evaluateString(?string $target, ConditionOperator $operator, mixed $value, bool $uppercase = false): bool
    {
        $target = $this->normalizeString($target, $uppercase);

        if ($operator === ConditionOperator::Exists) {
            return $target !== null && $target !== '';
        }

        if ($operator === ConditionOperator::NotExists) {
            return $target === null || $target === '';
        }

        if ($target === null || $target === '') {
            return false;
        }

        $values = $this->normalizeValueList($value, $uppercase);

        return match ($operator) {
            ConditionOperator::Equals => $values !== [] && $target === $values[0],
            ConditionOperator::NotEquals => $values !== [] && $target !== $values[0],
            ConditionOperator::In => $values !== [] && in_array($target, $values, true),
            ConditionOperator::NotIn => $values !== [] && ! in_array($target, $values, true),
            ConditionOperator::Contains => $values !== [] && Str::contains($target, $values[0]),
            ConditionOperator::NotContains => $values !== [] && ! Str::contains($target, $values[0]),
            ConditionOperator::StartsWith => $values !== [] && Str::startsWith($target, $values[0]),
            ConditionOperator::EndsWith => $values !== [] && Str::endsWith($target, $values[0]),
            ConditionOperator::Regex => $this->evaluateRegex($target, $values[0] ?? null),
            default => false,
        };
    }

    private function evaluateRegex(string $target, ?string $pattern): bool
    {
        if (! config('links.dynamic.allow_regex')) {
            return false;
        }

        if (! is_string($pattern) || $pattern === '') {
            return false;
        }

        return (bool) preg_match($pattern, $target);
    }

    /**
     * @return array<int, string>
     */
    private function normalizeValueList(mixed $value, bool $uppercase): array
    {
        $values = is_array($value) ? $value : [$value];

        return array_values(array_filter(array_map(function ($item) use ($uppercase) {
            if (! is_string($item)) {
                return null;
            }

            return $this->normalizeString($item, $uppercase);
        }, $values)));
    }

    private function acceptLanguage(?string $header): ?string
    {
        if (! is_string($header) || $header === '') {
            return null;
        }

        $parts = explode(',', $header);
        $primary = trim($parts[0] ?? '');

        if ($primary === '') {
            return null;
        }

        $segments = explode(';', $primary);

        return $this->normalizeString($segments[0] ?? null);
    }

    private function normalizeString(?string $value, bool $uppercase = false): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        return $uppercase ? Str::upper($trimmed) : Str::lower($trimmed);
    }

    private function deviceType(?string $userAgent): ?string
    {
        if (! is_string($userAgent) || $userAgent === '') {
            return null;
        }

        $agent = Str::lower($userAgent);

        if (Str::contains($agent, ['ipad', 'tablet'])) {
            return DeviceType::Tablet->value;
        }

        if (Str::contains($agent, ['mobile', 'iphone', 'android'])) {
            return DeviceType::Mobile->value;
        }

        return DeviceType::Desktop->value;
    }

    private function operatingSystem(?string $userAgent): ?string
    {
        if (! is_string($userAgent) || $userAgent === '') {
            return null;
        }

        $agent = Str::lower($userAgent);

        if (Str::contains($agent, ['iphone', 'ipad', 'ios'])) {
            return OperatingSystem::IOS->value;
        }

        if (Str::contains($agent, 'android')) {
            return OperatingSystem::Android->value;
        }

        if (Str::contains($agent, 'windows')) {
            return OperatingSystem::Windows->value;
        }

        if (Str::contains($agent, ['mac os', 'macintosh'])) {
            return OperatingSystem::MacOS->value;
        }

        if (Str::contains($agent, 'linux')) {
            return OperatingSystem::Linux->value;
        }

        return null;
    }

    private function browserName(?string $userAgent): ?string
    {
        if (! is_string($userAgent) || $userAgent === '') {
            return null;
        }

        $agent = Str::lower($userAgent);

        if (Str::contains($agent, 'edg/')) {
            return BrowserName::Edge->value;
        }

        if (Str::contains($agent, 'chrome/') && ! Str::contains($agent, 'edg/')) {
            return BrowserName::Chrome->value;
        }

        if (Str::contains($agent, 'firefox/')) {
            return BrowserName::Firefox->value;
        }

        if (Str::contains($agent, 'safari/') && ! Str::contains($agent, 'chrome/')) {
            return BrowserName::Safari->value;
        }

        return BrowserName::Other->value;
    }

    private function countryCode(Request $request): ?string
    {
        $ip = $request->ip();

        if (! is_string($ip) || $ip === '') {
            return null;
        }

        $country = $this->ipCountryResolver->resolve($ip);

        if (! is_string($country) || $country === '') {
            return null;
        }

        return Str::upper($country);
    }
}
