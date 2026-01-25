<?php

namespace App\Http\Controllers\Api;

use App\Actions\Domains\VerifyDomain;
use App\Events\DomainCreated;
use App\Events\DomainDeleted;
use App\Events\DomainDisabled;
use App\Events\DomainVerified;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domains\StoreDomainRequest;
use App\Http\Requests\Domains\VerifyDomainRequest;
use App\Http\Responses\ApiErrorResponse;
use App\Http\Responses\DomainResponse;
use App\Models\Domain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DomainsController extends Controller
{
    public function index(Request $request)
    {
        $domains = Domain::query()
            ->where('user_id', $request->user()->id)
            ->withCount('links')
            ->latest()
            ->get();

        return DomainResponse::collection($domains);
    }

    public function store(StoreDomainRequest $request): JsonResponse
    {
        $domain = Domain::query()->create([
            'user_id' => $request->user()->id,
            'hostname' => $request->string('hostname')->lower()->toString(),
            'type' => Domain::TYPE_CUSTOM,
            'status' => Domain::STATUS_PENDING,
            'verification_method' => Domain::VERIFICATION_DNS,
            'verification_token' => $this->verificationTarget(),
            'verified_at' => null,
        ]);

        event(new DomainCreated($domain));

        return (new DomainResponse($domain))
            ->withMessage('Domain added. Add the CNAME record to verify ownership.')
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Domain $domain): DomainResponse
    {
        $domain = $this->resolveDomainForUser($request, $domain);

        return (new DomainResponse($domain))->withMessage('Domain details.');
    }

    public function destroy(Request $request, Domain $domain): JsonResponse
    {
        $domain = $this->resolveDomainForUser($request, $domain);

        if ($domain->links()->exists()) {
            return ApiErrorResponse::make('Remove links on this domain before deleting it.', status: 409);
        }

        event(new DomainDeleted($domain));

        $domain->delete();

        return response()->json([
            'message' => 'Domain removed.',
        ]);
    }

    public function verify(
        VerifyDomainRequest $request,
        Domain $domain,
    ): JsonResponse {
        $verifier = app(VerifyDomain::class);
        $domain = $this->resolveDomainForUser($request, $domain);
        $expectedTarget = $this->verificationTarget();

        if ($domain->status === Domain::STATUS_DISABLED) {
            return ApiErrorResponse::make('This domain is disabled.', status: 409);
        }

        if ($domain->verification_token !== $expectedTarget) {
            $domain->forceFill([
                'verification_token' => $expectedTarget,
            ])->save();
        }

        $result = $verifier->verify($domain);

        if ($result['success']) {
            $domain->forceFill([
                'status' => Domain::STATUS_VERIFIED,
                'verified_at' => now(),
            ])->save();

            event(new DomainVerified($domain));

            return (new DomainResponse($domain))
                ->withMessage($result['message'])
                ->response();
        }

        return ApiErrorResponse::make($result['message'], status: 422);
    }

    public function disable(Request $request, Domain $domain): JsonResponse
    {
        $domain = $this->resolveDomainForUser($request, $domain);

        $domain->forceFill([
            'status' => Domain::STATUS_DISABLED,
        ])->save();

        event(new DomainDisabled($domain));

        return response()->json([
            'message' => 'Domain disabled.',
        ]);
    }

    private function resolveDomainForUser(Request $request, Domain $domain): Domain
    {
        if ($domain->user_id !== $request->user()->id) {
            abort(404);
        }

        return $domain;
    }

    private function verificationTarget(): string
    {
        $target = config('links.domain_verification_cname');

        if (! is_string($target) || trim($target) === '') {
            return parse_url(config('app.url'), PHP_URL_HOST) ?? '';
        }

        return Str::lower(trim($target));
    }
}
