<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpenseShare extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'amount_cents', 'basis_points'];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'basis_points' => 'integer',
        ];
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
