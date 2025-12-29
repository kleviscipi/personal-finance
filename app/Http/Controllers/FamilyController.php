<?php

namespace App\Http\Controllers;

use App\Models\AccountInvitation;
use App\Models\User;
use App\Notifications\FamilyInviteNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FamilyController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $account = $request->user()->accounts()->first();
        if (!$account) {
            return redirect()->route('accounts.create');
        }

        $members = $account->users()
            ->orderBy('name')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->pivot->role,
                    'is_active' => (bool) $user->pivot->is_active,
                ];
            });

        return Inertia::render('Family/Index', [
            'members' => $members,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $account = $request->user()->accounts()->first();
        if (!$account) {
            return redirect()->route('accounts.create');
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', Rule::in(['owner', 'admin', 'member', 'viewer'])],
        ]);

        $token = Str::random(40);
        $tokenHash = hash('sha256', $token);

        $invitation = AccountInvitation::updateOrCreate(
            [
                'account_id' => $account->id,
                'email' => $validated['email'],
                'accepted_at' => null,
            ],
            [
                'invited_by' => $request->user()->id,
                'role' => $validated['role'],
                'token_hash' => $tokenHash,
                'expires_at' => now()->addDays(7),
            ],
        );

        $inviteLink = route('invites.show', $token);

        $user = User::where('email', $validated['email'])->first();
        if (!$user) {
            $user = new User([
                'name' => Str::title(Str::before($validated['email'], '@')),
                'email' => $validated['email'],
                'password' => Hash::make(Str::random(32)),
            ]);
            $user->exists = false;
        }

        $user->notify(new FamilyInviteNotification($account, $inviteLink, $request->user()));

        return redirect()
            ->route('family.index')
            ->with('message', 'Invite sent successfully.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $account = $request->user()->accounts()->first();
        if (!$account) {
            return redirect()->route('accounts.create');
        }

        $validated = $request->validate([
            'role' => ['required', Rule::in(['owner', 'admin', 'member', 'viewer'])],
            'is_active' => ['required', 'boolean'],
        ]);

        $account->users()->updateExistingPivot($user->id, [
            'role' => $validated['role'],
            'is_active' => $validated['is_active'],
        ]);

        return redirect()
            ->route('family.index')
            ->with('message', 'Member updated successfully.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $account = $request->user()->accounts()->first();
        if (!$account) {
            return redirect()->route('accounts.create');
        }

        $account->users()->detach($user->id);

        return redirect()
            ->route('family.index')
            ->with('message', 'Member removed successfully.');
    }
}
