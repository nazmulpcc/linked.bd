<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiErrorResponse;
use App\Models\Link;
use Illuminate\Http\Request;

class LinkQrController extends Controller
{
    public function show(Request $request, Link $link): ApiErrorResponse
    {
        return ApiErrorResponse::make('QR API not implemented yet.', status: 501);
    }
}
