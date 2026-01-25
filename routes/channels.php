<?php

use App\Models\BulkImportJob;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('bulk-imports.{job}', function ($user, string $job) {
    return BulkImportJob::query()
        ->where('id', $job)
        ->where('user_id', $user->id)
        ->exists();
});
