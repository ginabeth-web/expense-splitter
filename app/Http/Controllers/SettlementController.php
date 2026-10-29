<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettlementRequest;
use App\Models\Group;
use App\Models\Settlement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class SettlementController extends Controller
{
    public function store(SettlementRequest $request, Group $group): RedirectResponse
    {
        $group->settlements()->create([
            ...$request->settlementAttributes(),
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('groups.show', $group)->with('success', 'Payment recorded.');
    }

    public function destroy(Group $group, Settlement $settlement): RedirectResponse
    {
        Gate::authorize('update', $group);

        $settlement->delete();

        return redirect()->route('groups.show', $group)->with('success', 'Payment deleted.');
    }
}
