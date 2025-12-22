<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $password   
 * @property string $cpf
 * @property Role $role
 * @property \Illuminate\Database\Eloquent\Collection $wallets
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'cpf',
        'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
        ];
    }

    /**
     * Check if the user has a specific role.
     */
    public function hasRole(Role|string $role): bool
    {
        if ($role instanceof Role) {
            return $this->role === $role;
        }

        return $this->role->value === $role;

    }

    /**
     * Check if the user is a customer.
     */
    public function isCustomer(): bool
    {
        return $this->hasRole(Role::CUSTOMER);
    }

    /**
     * Check if the user is a cliente.
     */
    public function isStoreKeeper(): bool
    {
        return $this->hasRole(Role::STORE_KEEPER);
    }

    /**
     * Get the wallet associated with the user.
     */
    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }
}
