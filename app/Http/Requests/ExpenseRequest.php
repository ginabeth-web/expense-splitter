<?php

namespace App\Http\Requests;

use App\Exceptions\InvalidSplitException;
use App\Models\Group;
use App\Services\SplitCalculator;
use App\Support\Money;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ExpenseRequest extends FormRequest
{
    /** @var array<int, int> */
    private array $computedShares = [];

    /** @var array<int, int> */
    private array $basisPoints = [];

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->group());
    }

    public function rules(): array
    {
        $memberIds = $this->memberIds();

        return [
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', function (string $attribute, mixed $value, Closure $fail) {
                if (! is_scalar($value) || Money::toCents((string) $value) === null) {
                    $fail('Enter an amount like 250 or 99.50.');
                }
            }],
            'spent_on' => ['required', 'date'],
            'paid_by' => ['required', 'integer', Rule::in($memberIds)],
            'split_type' => ['required', Rule::in(['equal', 'exact', 'percent'])],
            'participants' => ['array', 'required_if:split_type,equal'],
            'participants.*' => ['integer', Rule::in($memberIds)],
            'shares' => ['nullable', 'array'],
            'percents' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'paid_by.in' => 'The payer must be in this group.',
            'participants.*.in' => 'Everyone sharing must be in this group.',
            'participants.required_if' => 'Choose at least one person to share this expense.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $type = $this->input('split_type');
                $field = match ($type) {
                    'exact' => 'shares',
                    'percent' => 'percents',
                    default => 'participants',
                };

                if ($type === 'equal') {
                    $inputs = array_map('intval', $this->input('participants', []));
                } else {
                    $inputs = $this->parseAmounts($field);
                    if ($inputs === null) {
                        $validator->errors()->add($field, $type === 'exact'
                            ? 'Enter each amount like 250 or 99.50, or leave it blank.'
                            : 'Enter each percentage like 25 or 33.33, or leave it blank.');

                        return;
                    }
                }

                try {
                    $this->computedShares = app(SplitCalculator::class)->split(
                        Money::toCents((string) $this->input('amount')),
                        $type,
                        $inputs,
                    );
                    $this->basisPoints = $type === 'percent' ? $inputs : [];
                } catch (InvalidSplitException $e) {
                    $validator->errors()->add($field, $e->getMessage());
                }
            },
        ];
    }

    public function group(): Group
    {
        return $this->route('group');
    }

    /** @return array<string, mixed> */
    public function expenseAttributes(): array
    {
        return [
            'description' => $this->validated('description'),
            'amount_cents' => Money::toCents((string) $this->validated('amount')),
            'spent_on' => $this->validated('spent_on'),
            'paid_by' => (int) $this->validated('paid_by'),
            'split_type' => $this->validated('split_type'),
        ];
    }

    /** @return list<array{user_id: int, amount_cents: int, basis_points: int|null}> */
    public function shareRows(): array
    {
        $rows = [];
        foreach ($this->computedShares as $userId => $cents) {
            $rows[] = [
                'user_id' => $userId,
                'amount_cents' => $cents,
                'basis_points' => $this->basisPoints[$userId] ?? null,
            ];
        }

        return $rows;
    }

    /**
     * Parse a [userId => "12.50"] map, skipping blank rows. Returns null if any
     * row is malformed or names someone outside the group.
     *
     * @return array<int, int>|null
     */
    private function parseAmounts(string $field): ?array
    {
        $memberIds = $this->memberIds();
        $parsed = [];

        foreach ($this->input($field) ?? [] as $userId => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $cents = is_scalar($value) ? Money::toCents((string) $value) : null;
            if ($cents === null || ! in_array((int) $userId, $memberIds, true)) {
                return null;
            }

            $parsed[(int) $userId] = $cents;
        }

        return $parsed;
    }

    /** @return list<int> */
    private function memberIds(): array
    {
        return $this->group()->members()->pluck('users.id')->all();
    }
}
