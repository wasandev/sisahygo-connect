<?php

namespace App\Http\Middleware;

use App\Domain\ClientAccount\Enums\CurrentClientAccountResolutionStatus;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Services\CurrentClientAccountResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClientAccountSelected
{
    public function __construct(private readonly CurrentClientAccountResolver $resolver) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $resolution = $this->resolver->resolve(
            $user,
            $request->session()->get(CurrentClientAccountResolver::SESSION_KEY),
        );

        if ($resolution->status === CurrentClientAccountResolutionStatus::NoAccounts) {
            $request->session()->forget(CurrentClientAccountResolver::SESSION_KEY);

            return response()->view('client-accounts.unavailable', status: 403);
        }

        if ($resolution->status === CurrentClientAccountResolutionStatus::InvalidSelection) {
            $request->session()->forget(CurrentClientAccountResolver::SESSION_KEY);

            return redirect()->route('client-accounts.select')
                ->with('status', __('account_selection.invalid_selection'));
        }

        if ($resolution->status === CurrentClientAccountResolutionStatus::SelectionRequired) {
            return redirect()->route('client-accounts.select');
        }

        $clientAccount = $resolution->clientAccount;
        $request->session()->put(CurrentClientAccountResolver::SESSION_KEY, $clientAccount->id);
        app()->instance(ClientAccount::class, $clientAccount);

        return $next($request);
    }
}
