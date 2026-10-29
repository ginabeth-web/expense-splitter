<?php

namespace App\Http\Requests;

use App\Models\Group;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class AddMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('group'));
    }

    public function rules(): array
    {
        /** @var Group $group */
        $group = $this->route('group');

        return [
            'email' => [
                'required',
                'email',
                'exists:users,email',
                function (string $attribute, mixed $value, Closure $fail) use ($group) {
                    if ($group->members()->where('email', $value)->exists()) {
                        $fail('That person is already in this group.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return ['email.exists' => 'No one has signed up with that email yet.'];
    }
}
