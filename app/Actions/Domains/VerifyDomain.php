<?php

namespace App\Actions\Domains;

use App\Models\Domain;

class VerifyDomain
{
    /**
     * @return array{success: bool, message: string}
     */
    public function verify(Domain $domain): array
    {
        if ($domain->verification_method !== Domain::VERIFICATION_DNS) {
            return [
                'success' => false,
                'message' => 'Domain verification method is invalid.',
            ];
        }

        $expectedTarget = $this->expectedTarget();

        if ($expectedTarget === '') {
            return [
                'success' => false,
                'message' => 'Verification target is not configured.',
            ];
        }

        $recordName = $domain->hostname;
        $records = dns_get_record($recordName, DNS_CNAME);

        if (! is_array($records)) {
            return [
                'success' => false,
                'message' => 'No CNAME record found yet.',
            ];
        }

        foreach ($records as $record) {
            $value = $record['target'] ?? $record['cname'] ?? null;

            if ($value && $this->normalizeCname($value) === $this->normalizeCname($expectedTarget)) {
                return [
                    'success' => true,
                    'message' => 'Domain verified.',
                ];
            }
        }

        return [
            'success' => false,
            'message' => 'CNAME record does not point to the expected target.',
        ];
    }

    public function handle(Domain $domain): bool
    {
        return $this->verify($domain)['success'];
    }

    private function expectedTarget(): string
    {
        $target = config('links.domain_verification_cname');

        if (! is_string($target) || trim($target) === '') {
            return parse_url(config('app.url'), PHP_URL_HOST) ?? '';
        }

        return trim($target);
    }

    private function normalizeCname(string $value): string
    {
        return rtrim(strtolower(trim($value)), '.');
    }
}
