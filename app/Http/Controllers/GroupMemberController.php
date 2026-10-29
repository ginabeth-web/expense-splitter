<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddMemberRequest;
use App\Models\Group;
use App\Models\User;
use App\Services\BalanceCalculator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class GroupMemberController extends Controller
{
    public function store(AddMemberRequest $request, Group $group): RedirectResponse
    {
        $user = User::where('email', $request->validated('email'))->firstOrFail();
        $group->members()->attach($user);

        return redirect()->route('groups.show', $group)->with('success', "{$user->name} was added.");
    }

    public function destroy(Group $group, User $user, BalanceCalculator $calculator): RedirectResponse
    {
        Gate::authorize('update', $group);
        abort_unless($group->hasMember($user), 404);

        if ($group->members()->count() === 1) {
            throw ValidationException::withMessages(['member' => 'A group needs at least one member.']);
        }

        if (($calculator->forGroup($group)[$user->id] ?? 0) !== 0) {
            throw ValidationException::withMessages([
                'member' => "{$user->name} still has an unsettled balance. Settle up first.",
            ]);
        }

        // Old expenses and payments still name them, so removing them would leave
        // records nobody can edit or settle.
        if ($this->hasHistory($group, $user)) {
            throw ValidationException::withMessages([
                'member' => "{$user->name} is part of expenses or payments in this group, so they can't be removed.",
            ]);
        }

        $group->members()->detach($user);

        return redirect()->route('groups.show', $group)->with('success', "{$user->name} was removed.");
    }

    private function hasHistory(Group $group, User $user): bool
    {
        $id = $user->id;

        return $group->expenses()
            ->where(fn ($query) => $query
                ->where('paid_by', $id)
                ->orWhere('created_by', $id)
                ->orWhereHas('shares', fn ($shares) => $shares->where('user_id', $id)))
            ->exists()
            || $group->settlements()
                ->where(fn ($query) => $query
                    ->where('from_user_id', $id)
                    ->orWhere('to_user_id', $id)
                    ->orWhere('created_by', $id))
                ->exists();
    }
}
