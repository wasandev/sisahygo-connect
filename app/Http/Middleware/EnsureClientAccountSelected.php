<?php

namespace App\Http\Middleware;

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

        $clientAccount = $this->resolver->resolveForUser($user);

        if (! $clientAccount) {
            abort(403, 'No active client account is available for this user.');
        }

        app()->instance(ClientAccount::class, $clientAccount);

        return $next($request);
    }
}