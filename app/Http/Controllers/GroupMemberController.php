<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddMemberRequest;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class GroupMemberController extends Controller
{
    public function store(AddMemberRequest $request, Group $group): RedirectResponse
    {
        $user = User::where('email', $request->validated('email'))->firstOrFail();
        $group->members()->attach($user);

        return redirect()->route('groups.show', $group)->with('success', "{$user->name} was added.");
    }
}
