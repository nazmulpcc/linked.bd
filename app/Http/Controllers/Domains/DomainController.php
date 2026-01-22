<?php

namespace App\Http\Controllers\Domains;

use App\Actions\Domains\VerifyDomain;
use App\Events\DomainCreated;
use App\Events\DomainDeleted;
use App\Events\DomainDisabled;
use App\Events\DomainVerified;
use App\Http\Controllers\Controller;
use App\Http\Requests\Domains\DestroyDomainRequest;
use App\Http\Requests\Domains\DisableDomainRequest;
use App\Http\Requests\Domains\StoreDomainRequest;
use App\Http\Requests\Domains\VerifyDomainRequest;
use App\Models\Domain;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DomainController extends Controller
{
    public function index(Request $request): Response
    {
        $domains = Domain::query()
            ->where('user_id', $request->user()->id)
            ->withCount('links')
            ->latest()
            ->get();

        return Inertia::render('domains/Index', [
            'domains' => $domains,
        ]);
    }

    public function store(StoreDomainRequest $request): RedirectResponse
    {
        $domain = Domain::query()->create([
            'user_id' => $request->user()->id,
            'hostname' => $request->string('hostname')->lower()->toString(),
            'type' => Domain::TYPE_CUSTOM,
            'status' => Domain::STATUS_PENDING,
            'verification_method' => Domain::VERIFICATION_DNS,
            'verification_token' => Str::random(32),
            'verified_at' => null,
        ]);

        event(new DomainCreated($domain));

        return to_route('domains.index')->with(
            'success',
            'Domain added. Add the TXT record to verify ownership.',
        );
    }

    public function verify(
        VerifyDomainRequest $request,
        Domain $domain,
        VerifyDomain $verifier,
    ): RedirectResponse {
        $domain = $this->resolveDomainForUser($request, $domain);

        if ($domain->status === Domain::STATUS_DISABLED) {
            return to_route('domains.index')->with('error', 'This domain is disabled.');
        }

        if ($verifier->handle($domain)) {
            $domain->forceFill([
                'status' => Domain::STATUS_VERIFIED,
                'verified_at' => now(),
            ])->save();

            event(new DomainVerified($domain));

            return to_route('domains.index')->with('success', 'Domain verified.');
        }

        return to_route('domains.index')->with('error', 'Verification failed. Check your DNS record.');
    }

    public function disable(
        DisableDomainRequest $request,
        Domain $domain,
    ): RedirectResponse {
        $domain = $this->resolveDomainForUser($request, $domain);

        $domain->forceFill([
            'status' => Domain::STATUS_DISABLED,
        ])->save();

        event(new DomainDisabled($domain));

        return to_route('domains.index')->with('success', 'Domain disabled.');
    }

    public function destroy(
        DestroyDomainRequest $request,
        Domain $domain,
    ): RedirectResponse {
        $domain = $this->resolveDomainForUser($request, $domain);

        if ($domain->links()->exists()) {
            return to_route('domains.index')->with(
                'error',
                'Remove links on this domain before deleting it.',
            );
        }

        event(new DomainDeleted($domain));

        $domain->delete();

        return to_route('domains.index')->with('success', 'Domain removed.');
    }

    private function resolveDomainForUser(Request $request, Domain $domain): Domain
    {
        if ($domain->user_id !== $request->user()->id) {
            abort(404);
        }

        return $domain;
    }
}
