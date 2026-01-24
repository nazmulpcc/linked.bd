<?php

namespace App\Actions\Domains;

use App\Models\Domain;

class VerifyDomain
{
    public function handle(Domain $domain): bool
    {
        if ($domain->verification_method !== Domain::VERIFICATION_DNS) {
            return false;
        }

        $expectedTarget = $this->expectedTarget();

        if ($expectedTarget === '') {
            return false;
        }

        $recordName = $domain->hostname;
        $records = dns_get_record($recordName, DNS_CNAME);

        if (! is_array($records)) {
            return false;
        }

        foreach ($records as $record) {
            $value = $record['target'] ?? $record['cname'] ?? null;

            if ($value && $this->normalizeCname($value) === $this->normalizeCname($expectedTarget)) {
                return true;
            }
        }

        return false;
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
