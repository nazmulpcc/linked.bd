<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiErrorResponse;
use App\Models\Link;
use Illuminate\Http\Request;

class LinksController extends Controller
{
    public function index(Request $request): ApiErrorResponse
    {
        return ApiErrorResponse::make('Links API not implemented yet.', status: 501);
    }

    public function store(Request $request): ApiErrorResponse
    {
        return ApiErrorResponse::make('Links API not implemented yet.', status: 501);
    }

    public function show(Request $request, Link $link): ApiErrorResponse
    {
        return ApiErrorResponse::make('Links API not implemented yet.', status: 501);
    }

    public function destroy(Request $request, Link $link): ApiErrorResponse
    {
        return ApiErrorResponse::make('Links API not implemented yet.', status: 501);
    }
}
