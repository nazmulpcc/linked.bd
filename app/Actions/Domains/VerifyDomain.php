<?php

namespace App\Actions\Domains;

use App\Models\Domain;

class VerifyDomain
{
    public function handle(Domain $domain): bool
    {
        if ($domain->verification_method !== 'dns_txt' || ! $domain->verification_token) {
            return false;
        }

        $recordName = $domain->verificationRecordName();
        $records = dns_get_record($recordName, DNS_TXT);

        if (! is_array($records)) {
            return false;
        }

        foreach ($records as $record) {
            $value = $record['txt'] ?? $record['txtdata'] ?? null;

            if ($value && trim($value) === $domain->verification_token) {
                return true;
            }
        }

        return false;
    }
}
