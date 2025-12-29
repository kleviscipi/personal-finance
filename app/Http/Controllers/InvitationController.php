<?php

namespace App\Http\Controllers;

use App\Models\AccountInvitation;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class InvitationController extends Controller
{
    public function show(string $token): Response
    {
        $invitation = $this->findInvitation($token);

        if (!$invitation) {
            abort(404);
        }

        return Inertia::render('Invites/Accept', [
            'invite' => [
                'token' => $token,
                'email' => $invitation->email,
                'role' => $invitation->role,
                'account' => [
                    'name' => $invitation->account->name,
                ],
            ],
        ]);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = $this->findInvitation($token);
        if (!$invitation) {
            return redirect()->route('login')->withErrors([
                'email' => 'This invitation is no longer valid.',
            ]);
        }

        $user = $request->user();
        if (!$user) {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            $user = User::firstOrCreate(
                ['email' => $invitation->email],
                [
                    'name' => $validated['name'],
                    'password' => Hash::make($validated['password']),
                ]
            );

            Auth::login($user);
        } elseif ($user->email !== $invitation->email) {
            return redirect()->route('login')->withErrors([
                'email' => 'Please sign in with ' . $invitation->email . ' to accept this invite.',
            ]);
        }

        $invitation->account->users()->syncWithoutDetaching([
            $user->id => [
                'role' => $invitation->role,
                'is_active' => true,
                'invited_at' => $invitation->created_at,
                'joined_at' => now(),
            ],
        ]);

        $invitation->update([
            'accepted_at' => now(),
        ]);

        return redirect()->route('dashboard');
    }

    private function findInvitation(string $token): ?AccountInvitation
    {
        $tokenHash = hash('sha256', $token);

        return AccountInvitation::with('account')
            ->where('token_hash', $tokenHash)
            ->whereNull('accepted_at')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
    }
}
