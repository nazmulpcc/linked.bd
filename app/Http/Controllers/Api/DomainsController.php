<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiErrorResponse;
use App\Models\Domain;
use Illuminate\Http\Request;

class DomainsController extends Controller
{
    public function index(Request $request): ApiErrorResponse
    {
        return ApiErrorResponse::make('Domains API not implemented yet.', status: 501);
    }

    public function store(Request $request): ApiErrorResponse
    {
        return ApiErrorResponse::make('Domains API not implemented yet.', status: 501);
    }

    public function show(Request $request, Domain $domain): ApiErrorResponse
    {
        return ApiErrorResponse::make('Domains API not implemented yet.', status: 501);
    }

    public function destroy(Request $request, Domain $domain): ApiErrorResponse
    {
        return ApiErrorResponse::make('Domains API not implemented yet.', status: 501);
    }

    public function verify(Request $request, Domain $domain): ApiErrorResponse
    {
        return ApiErrorResponse::make('Domains API not implemented yet.', status: 501);
    }
}
