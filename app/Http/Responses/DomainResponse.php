<?php

namespace App\Http\Responses;

use App\Models\Domain;
use Illuminate\Http\Request;

class DomainResponse extends ApiResponse
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Domain $domain */
        $domain = $this->resource;

        return [
            'id' => $domain->id,
            'hostname' => $domain->hostname,
            'type' => $domain->type,
            'status' => $domain->status,
            'verification_method' => $domain->verification_method,
            'verified_at' => optional($domain->verified_at)->toIso8601String(),
            'created_at' => optional($domain->created_at)->toIso8601String(),
        ];
    }
}
