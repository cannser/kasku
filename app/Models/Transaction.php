<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    // user_id TIDAK fillable: selalu diisi dari relasi pengguna yang sedang login
    protected $fillable = ['category_id', 'type', 'amount', 'description', 'trx_date'];

    protected function casts(): array
    {
        return [
            'amount'   => 'decimal:2',
            'trx_date' => 'date:Y-m-d',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
