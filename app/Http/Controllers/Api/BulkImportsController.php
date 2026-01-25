<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiErrorResponse;
use Illuminate\Http\Request;

class BulkImportsController extends Controller
{
    public function store(Request $request): ApiErrorResponse
    {
        return ApiErrorResponse::make('Bulk import API not implemented yet.', status: 501);
    }

    public function show(Request $request, string $job): ApiErrorResponse
    {
        return ApiErrorResponse::make('Bulk import API not implemented yet.', status: 501);
    }

    public function items(Request $request, string $job): ApiErrorResponse
    {
        return ApiErrorResponse::make('Bulk import API not implemented yet.', status: 501);
    }
}
