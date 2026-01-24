<?php

namespace App\Actions\Domains;

use App\Models\Domain;

class VerifyDomain
{
    public function handle(Domain $domain): bool
    {
        if ($domain->verification_method !== Domain::VERIFICATION_DNS || ! $domain->verification_token) {
            return false;
        }

        $recordName = $domain->hostname;
        $records = dns_get_record($recordName, DNS_CNAME);

        if (! is_array($records)) {
            return false;
        }

        foreach ($records as $record) {
            $value = $record['target'] ?? $record['cname'] ?? null;

            if ($value && $this->normalizeCname($value) === $this->normalizeCname($domain->verification_token)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeCname(string $value): string
    {
        return rtrim(strtolower(trim($value)), '.');
    }
}
