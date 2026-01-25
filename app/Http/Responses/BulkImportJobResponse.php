<?php

namespace App\Http\Responses;

use App\Models\BulkImportJob;
use Illuminate\Http\Request;

class BulkImportJobResponse extends ApiResponse
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var BulkImportJob $job */
        $job = $this->resource;

        return [
            'id' => $job->id,
            'domain_id' => $job->domain_id,
            'status' => $job->status,
            'total_count' => $job->total_count,
            'processed_count' => $job->processed_count,
            'success_count' => $job->success_count,
            'failed_count' => $job->failed_count,
            'default_expires_at' => optional($job->default_expires_at)->toIso8601String(),
            'created_at' => optional($job->created_at)->toIso8601String(),
            'started_at' => optional($job->started_at)->toIso8601String(),
            'finished_at' => optional($job->finished_at)->toIso8601String(),
        ];
    }
}
