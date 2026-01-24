<?php

namespace App\Http\Requests\Links;

use App\Enums\BrowserName;
use App\Enums\ConditionOperator;
use App\Enums\ConditionType;
use App\Enums\DayOfWeek;
use App\Enums\DeviceType;
use App\Enums\OperatingSystem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateDynamicLinkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'fallback_destination_url' => [
                'required',
                'string',
                'max:2048',
                'url',
                'starts_with:http://,https://',
            ],
            'rules' => [
                'required',
                'array',
                'min:1',
                'max:'.config('links.dynamic.max_rules'),
            ],
            'rules.*.priority' => [
                'required',
                'integer',
                'min:1',
            ],
            'rules.*.destination_url' => [
                'required',
                'string',
                'max:2048',
                'url',
                'starts_with:http://,https://',
            ],
            'rules.*.enabled' => [
                'nullable',
                'boolean',
            ],
            'rules.*.conditions' => [
                'required',
                'array',
                'min:1',
                'max:'.config('links.dynamic.max_conditions_per_rule'),
            ],
            'rules.*.conditions.*.condition_type' => [
                'required',
                'string',
                Rule::in(ConditionType::values()),
            ],
            'rules.*.conditions.*.operator' => [
                'required',
                'string',
                Rule::in(ConditionOperator::values()),
            ],
            'rules.*.conditions.*.value' => [
                'nullable',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'fallback_destination_url.required' => 'Enter a fallback destination URL.',
            'fallback_destination_url.url' => 'Enter a valid fallback URL.',
            'fallback_destination_url.starts_with' => 'Fallback URLs must start with http:// or https://.',
            'rules.required' => 'Add at least one destination rule.',
            'rules.max' => 'Too many rules were added.',
            'rules.*.priority.required' => 'Each rule needs a priority.',
            'rules.*.destination_url.required' => 'Each rule needs a destination URL.',
            'rules.*.destination_url.url' => 'Rule destinations must be valid URLs.',
            'rules.*.destination_url.starts_with' => 'Rule destinations must start with http:// or https://.',
            'rules.*.conditions.required' => 'Each rule needs at least one condition.',
            'rules.*.conditions.max' => 'Too many conditions were added to a rule.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->validateDestinationUrls($validator);
            $this->validateDynamicRules($validator);
        });
    }

    private function validateDestinationUrls(Validator $validator): void
    {
        $urls = array_filter([
            $this->string('fallback_destination_url')->toString(),
            ...$this->ruleDestinationUrls(),
        ], fn (string $url) => $url !== '');

        foreach ($urls as $url) {
            $host = parse_url($url, PHP_URL_HOST);

            if (! is_string($host) || $host === '') {
                continue;
            }

            if ($this->isBlockedIp($host)) {
                $validator->errors()->add('fallback_destination_url', 'Destination URLs cannot target private or reserved IPs.');
                break;
            }
        }
    }

    private function ruleDestinationUrls(): array
    {
        $rules = $this->input('rules');

        if (! is_array($rules)) {
            return [];
        }

        $urls = [];

        foreach ($rules as $rule) {
            if (! is_array($rule)) {
                continue;
            }

            $destination = $rule['destination_url'] ?? null;

            if (is_string($destination)) {
                $urls[] = $destination;
            }
        }

        return $urls;
    }

    private function validateDynamicRules(Validator $validator): void
    {
        $rules = $this->input('rules');

        if (! is_array($rules)) {
            return;
        }

        $priorities = [];
        $totalConditions = 0;

        foreach ($rules as $ruleIndex => $rule) {
            if (! is_array($rule)) {
                continue;
            }

            $priority = $rule['priority'] ?? null;

            if (is_int($priority)) {
                $priorities[] = $priority;
            }

            $conditions = $rule['conditions'] ?? [];

            if (! is_array($conditions)) {
                continue;
            }

            $totalConditions += count($conditions);

            foreach ($conditions as $conditionIndex => $condition) {
                if (! is_array($condition)) {
                    continue;
                }

                $this->validateCondition($validator, $condition, $ruleIndex, $conditionIndex);
            }
        }

        if (count($priorities) !== count(array_unique($priorities))) {
            $validator->errors()->add('rules', 'Rule priorities must be unique.');
        }

        $maxTotalConditions = (int) config('links.dynamic.max_total_conditions');

        if ($totalConditions > $maxTotalConditions) {
            $validator->errors()->add('rules', 'Too many total conditions were added.');
        }
    }

    /**
     * @param  array<string, mixed>  $condition
     */
    private function validateCondition(
        Validator $validator,
        array $condition,
        int $ruleIndex,
        int $conditionIndex,
    ): void {
        $type = $condition['condition_type'] ?? null;
        $operator = $condition['operator'] ?? null;
        $value = $condition['value'] ?? null;

        if (! is_string($type) || ! is_string($operator)) {
            return;
        }

        $conditionType = ConditionType::tryFrom($type);
        $conditionOperator = ConditionOperator::tryFrom($operator);

        if (! $conditionType || ! $conditionOperator) {
            return;
        }

        if (! $this->operatorAllowedForType($conditionType, $conditionOperator)) {
            $validator->errors()->add(
                "rules.{$ruleIndex}.conditions.{$conditionIndex}.operator",
                'That operator is not allowed for the selected condition type.',
            );

            return;
        }

        if ($this->operatorExpectsNoValue($conditionOperator) && $value !== null && $value !== '') {
            $validator->errors()->add(
                "rules.{$ruleIndex}.conditions.{$conditionIndex}.value",
                'This operator does not accept a value.',
            );

            return;
        }

        if (! $this->operatorExpectsNoValue($conditionOperator)) {
            $this->validateConditionValue(
                $validator,
                $conditionType,
                $conditionOperator,
                $value,
                $ruleIndex,
                $conditionIndex,
            );
        }
    }

    private function operatorAllowedForType(ConditionType $type, ConditionOperator $operator): bool
    {
        $allowsRegex = (bool) config('links.dynamic.allow_regex');

        return in_array($operator, match ($type) {
            ConditionType::Country,
            ConditionType::DeviceType,
            ConditionType::OperatingSystem,
            ConditionType::Browser => [
                ConditionOperator::Equals,
                ConditionOperator::NotEquals,
                ConditionOperator::In,
                ConditionOperator::NotIn,
            ],
            ConditionType::ReferrerDomain,
            ConditionType::ReferrerPath => array_filter([
                ConditionOperator::Equals,
                ConditionOperator::NotEquals,
                ConditionOperator::Contains,
                ConditionOperator::NotContains,
                ConditionOperator::StartsWith,
                ConditionOperator::EndsWith,
                $allowsRegex ? ConditionOperator::Regex : null,
                ConditionOperator::Exists,
                ConditionOperator::NotExists,
            ]),
            ConditionType::UtmSource,
            ConditionType::UtmMedium,
            ConditionType::UtmCampaign => [
                ConditionOperator::Equals,
                ConditionOperator::NotEquals,
                ConditionOperator::In,
                ConditionOperator::NotIn,
                ConditionOperator::Contains,
                ConditionOperator::NotContains,
                ConditionOperator::Exists,
                ConditionOperator::NotExists,
            ],
            ConditionType::Language => [
                ConditionOperator::Equals,
                ConditionOperator::NotEquals,
                ConditionOperator::StartsWith,
                ConditionOperator::In,
                ConditionOperator::NotIn,
            ],
            ConditionType::TimeWindow => [
                ConditionOperator::Equals,
            ],
        }, true);
    }

    private function operatorExpectsNoValue(ConditionOperator $operator): bool
    {
        return in_array($operator, [ConditionOperator::Exists, ConditionOperator::NotExists], true);
    }

    private function validateConditionValue(
        Validator $validator,
        ConditionType $type,
        ConditionOperator $operator,
        mixed $value,
        int $ruleIndex,
        int $conditionIndex,
    ): void {
        $path = "rules.{$ruleIndex}.conditions.{$conditionIndex}.value";

        if ($type === ConditionType::TimeWindow) {
            if (! is_array($value)) {
                $validator->errors()->add($path, 'Time windows must be configured with an object value.');

                return;
            }

            $this->validateTimeWindow($validator, $value, $path);

            return;
        }

        if ($operator === ConditionOperator::In || $operator === ConditionOperator::NotIn) {
            if (! is_array($value) || $value === []) {
                $validator->errors()->add($path, 'This operator expects a list of values.');

                return;
            }
        } elseif (! is_string($value) || $value === '') {
            $validator->errors()->add($path, 'This operator expects a single value.');

            return;
        }

        $values = is_array($value) ? $value : [$value];

        foreach ($values as $singleValue) {
            if (! is_string($singleValue)) {
                $validator->errors()->add($path, 'This condition value must be text.');

                return;
            }

            if (! $this->valueMatchesType($type, $singleValue, $path, $validator)) {
                return;
            }
        }
    }

    private function valueMatchesType(
        ConditionType $type,
        string $value,
        string $path,
        Validator $validator,
    ): bool {
        return match ($type) {
            ConditionType::Country => $this->validateCountryValue($validator, $path, $value),
            ConditionType::DeviceType => $this->validateEnumValue($validator, $path, $value, DeviceType::values()),
            ConditionType::OperatingSystem => $this->validateEnumValue($validator, $path, $value, OperatingSystem::values()),
            ConditionType::Browser => $this->validateEnumValue($validator, $path, $value, BrowserName::values()),
            ConditionType::Language => $this->validateLanguageValue($validator, $path, $value),
            ConditionType::TimeWindow => true,
            default => true,
        };
    }

    private function validateEnumValue(
        Validator $validator,
        string $path,
        string $value,
        array $allowed,
    ): bool {
        if (! in_array($value, $allowed, true)) {
            $validator->errors()->add($path, 'This condition value is not supported.');

            return false;
        }

        return true;
    }

    private function validateCountryValue(
        Validator $validator,
        string $path,
        string $value,
    ): bool {
        if (! preg_match('/^[a-zA-Z]{2}$/', $value)) {
            $validator->errors()->add($path, 'Country values must be ISO-3166-1 alpha-2 codes.');

            return false;
        }

        return true;
    }

    private function validateLanguageValue(
        Validator $validator,
        string $path,
        string $value,
    ): bool {
        if (! preg_match('/^[a-zA-Z]{2,8}(-[a-zA-Z]{2,8})?$/', $value)) {
            $validator->errors()->add($path, 'Language values must be valid locale prefixes.');

            return false;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function validateTimeWindow(Validator $validator, array $value, string $path): void
    {
        $timezone = $value['timezone'] ?? null;

        if (! is_string($timezone) || $timezone === '') {
            $validator->errors()->add($path, 'Time windows must include a timezone.');

            return;
        }

        $days = $value['days'] ?? null;

        if ($days !== null) {
            if (! is_array($days) || $days === []) {
                $validator->errors()->add($path, 'Time window days must be a list of weekdays.');

                return;
            }

            foreach ($days as $day) {
                if (! is_string($day) || ! in_array($day, DayOfWeek::values(), true)) {
                    $validator->errors()->add($path, 'Time window days must use valid weekday names.');

                    return;
                }
            }
        }

        $hours = $value['hours'] ?? null;

        if ($hours !== null) {
            if (! is_array($hours)) {
                $validator->errors()->add($path, 'Time window hours must be an object with start and end.');

                return;
            }

            $start = $hours['start'] ?? null;
            $end = $hours['end'] ?? null;

            if (! is_int($start) || ! is_int($end)) {
                $validator->errors()->add($path, 'Time window hours must include integer start and end values.');

                return;
            }

            if ($start < 0 || $start > 23 || $end < 0 || $end > 23) {
                $validator->errors()->add($path, 'Time window hours must be between 0 and 23.');

                return;
            }
        }
    }

    private function isBlockedIp(string $host): bool
    {
        if (! filter_var($host, FILTER_VALIDATE_IP)) {
            return false;
        }

        return ! filter_var(
            $host,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        );
    }
}
