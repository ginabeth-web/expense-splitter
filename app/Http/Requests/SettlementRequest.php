<?php

namespace App\Http\Requests;

use App\Models\Group;
use App\Support\Money;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SettlementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->group());
    }

    public function rules(): array
    {
        $memberIds = $this->group()->members()->pluck('users.id')->all();

        return [
            'from_user_id' => ['required', 'integer', Rule::in($memberIds)],
            'to_user_id' => ['required', 'integer', Rule::in($memberIds), 'different:from_user_id'],
            'amount' => ['required', function (string $attribute, mixed $value, Closure $fail) {
                if (! is_scalar($value) || Money::toCents((string) $value) === null) {
                    $fail('Enter an amount like 250 or 99.50.');
                }
            }],
            'settled_on' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'from_user_id.in' => 'The person paying must be in this group.',
            'to_user_id.in' => 'The person being paid must be in this group.',
            'to_user_id.different' => 'Choose two different people.',
        ];
    }

    public function group(): Group
    {
        return $this->route('group');
    }

    /** @return array<string, mixed> */
    public function settlementAttributes(): array
    {
        return [
            'from_user_id' => (int) $this->validated('from_user_id'),
            'to_user_id' => (int) $this->validated('to_user_id'),
            'amount_cents' => Money::toCents((string) $this->validated('amount')),
            'settled_on' => $this->validated('settled_on'),
        ];
    }
}
