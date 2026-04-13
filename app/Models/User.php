<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;	

/**
 * Represents an application user.
 * 
 * A user can optionally become a seller by creating a seller profile.
 * Roles (admin, seller, buyer) are managed via Spatie Laravel Permission.
 * API authentication is handled via Laravel Sanctum tokens.
 * 
 * @property in          $id
 * @property string      $name
 * @property string      $email
 * @property stirng      $password
 * @property string|null $remember_token
 * @property \Carbon\Carbon|null $email_verified_at
 * @property \Carbon\Carban $created_at
 * @property \Carbon\Carbon $updated_at 
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
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
        ];
    }

    /**
     * Get the seller profile associated with the user.
     * 
     * A user becomes a seller by submitting an onboarding request.
     * The seller profile must be approved by an admin before the
     * user can list products.
     * 
     * @return HasOne<Seller>
     */
    public function seller(): HasOne
    {
        return $this->hasOne(Seller::class);
    }
}
