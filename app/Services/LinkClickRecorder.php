<?php

namespace App\Services;

use App\Jobs\RecordLinkClick;
use App\Models\Link;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Throwable;

class LinkClickRecorder
{
    public function record(
        Request $request,
        Link $link,
        ?int $ruleId,
        ?string $resolvedDestinationUrl,
    ): void {
        $visitData = $this->visitData($request, $ruleId, $resolvedDestinationUrl);

        try {
            RecordLinkClick::dispatch($link->id, $visitData);
        } catch (Throwable $exception) {
            $this->recordSync($link, $visitData);
        }
    }

    private function recordSync(Link $link, array $visitData): void
    {
        if ($link->expires_at && $link->expires_at->isPast()) {
            return;
        }

        $link->increment('click_count');
        $link->forceFill([
            'last_accessed_at' => now(),
        ])->save();

        $link->visits()->create($visitData);
    }

    /**
     * @return array{visited_at: string, referrer_host: string|null, device_type: string|null, browser: string|null, country_code: string|null, user_agent: string|null, link_rule_id: int|null, resolved_destination_url: string|null}
     */
    private function visitData(
        Request $request,
        ?int $ruleId,
        ?string $resolvedDestinationUrl,
    ): array {
        $userAgent = $request->userAgent();
        $referrer = $request->headers->get('referer');
        $referrerHost = null;

        if (is_string($referrer) && $referrer !== '') {
            $referrerHost = parse_url($referrer, PHP_URL_HOST);

            if (! is_string($referrerHost) || $referrerHost === '') {
                $referrerHost = null;
            }
        }

        return [
            'visited_at' => now()->toDateTimeString(),
            'referrer_host' => $referrerHost,
            'device_type' => $this->deviceType($userAgent),
            'browser' => $this->browserName($userAgent),
            'country_code' => $this->countryCode($request),
            'link_rule_id' => $ruleId,
            'resolved_destination_url' => $resolvedDestinationUrl,
            'user_agent' => $userAgent,
        ];
    }

    private function deviceType(?string $userAgent): ?string
    {
        if (! is_string($userAgent) || $userAgent === '') {
            return null;
        }

        $agent = Str::lower($userAgent);

        if (Str::contains($agent, ['mobile', 'iphone', 'android', 'ipad'])) {
            return 'mobile';
        }

        return 'desktop';
    }

    private function browserName(?string $userAgent): ?string
    {
        if (! is_string($userAgent) || $userAgent === '') {
            return null;
        }

        $agent = Str::lower($userAgent);

        if (Str::contains($agent, 'edg/')) {
            return 'edge';
        }

        if (Str::contains($agent, 'chrome/') && ! Str::contains($agent, 'edg/')) {
            return 'chrome';
        }

        if (Str::contains($agent, 'firefox/')) {
            return 'firefox';
        }

        if (Str::contains($agent, 'safari/') && ! Str::contains($agent, 'chrome/')) {
            return 'safari';
        }

        return 'other';
    }

    private function countryCode(Request $request): ?string
    {
        $ip = $request->ip();

        if (! is_string($ip) || $ip === '') {
            return null;
        }

        return app(IpCountryResolver::class)->resolve($ip);
    }
}
