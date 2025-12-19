<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'balance',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
        ];
    }

    /**
     * Get the user that owns the wallet.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get transfers where this wallet is the payer.
     */
    public function outgoingTransfers(): HasMany
    {
        return $this->hasMany(Transfer::class, 'payer_wallet_id');
    }

    /**
     * Get transfers where this wallet is the payee.
     */
    public function incomingTransfers(): HasMany
    {
        return $this->hasMany(Transfer::class, 'payee_wallet_id');
    }
}

