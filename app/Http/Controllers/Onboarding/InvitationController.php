<?php

namespace App\Http\Controllers\Onboarding;

use App\Domain\ClientAccount\Enums\ClientAccountRole;
use App\Domain\ClientAccount\Models\ClientAccount;
use App\Domain\ClientAccount\Models\ClientAccountUser;
use App\Domain\Onboarding\Models\AccessRequest;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InvitationController extends Controller
{
    public function show(string $token): View
    {
        $accessRequest = AccessRequest::query()
            ->where('invitation_token', $token)
            ->first();

        return view('onboarding.invitation', [
            'token' => $token,
            'accessRequest' => $accessRequest,
            'companyName' => $accessRequest?->company_name ?? 'บัญชีลูกค้าใหม่',
            'email' => $accessRequest?->email ?? '',
        ]);
    }

    public function activate(Request $request, string $token): RedirectResponse
    {
        $accessRequest = AccessRequest::query()
            ->where('invitation_token', $token)
            ->first();

        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [], __('onboarding.validation.attributes'));

        $user = User::query()->firstOrCreate(
            ['email' => $validated['email']],
            [
                'name' => $accessRequest?->contact_name ?: $validated['company_name'],
                'password' => Hash::make($validated['password']),
                'email_verified_at' => now(),
            ]
        );

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'email_verified_at' => $user->email_verified_at ?: now(),
        ])->save();

        $clientAccount = ClientAccount::query()->firstOrCreate(
            ['code' => $this->clientAccountCode($validated['company_name'])],
            ['name' => $validated['company_name'], 'status' => 'active']
        );

        ClientAccountUser::query()->firstOrCreate([
            'client_account_id' => $clientAccount->id,
            'user_id' => $user->id,
        ], [
            'role' => ClientAccountRole::Owner->value,
            'is_active' => true,
            'joined_at' => now(),
        ]);

        if ($accessRequest) {
            $accessRequest->forceFill([
                'status' => AccessRequest::STATUS_APPROVED,
                'activated_at' => now(),
            ])->save();
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/welcome');
    }

    private function clientAccountCode(string $companyName): string
    {
        $base = Str::upper(Str::slug($companyName, '-')) ?: 'CONNECT';
        $code = Str::limit($base, 24, '');
        $suffix = 1;

        while (ClientAccount::query()->where('code', $code)->exists()) {
            $code = Str::limit($base, 18, '').'-'.str_pad((string) $suffix, 3, '0', STR_PAD_LEFT);
            $suffix++;
        }

        return $code;
    }
}
