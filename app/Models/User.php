<?php
namespace App\Models;

<<<<<<< HEAD
=======
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
>>>>>>> origin/main
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
<<<<<<< HEAD
    use Notifiable;
=======
    use HasFactory, Notifiable;
>>>>>>> origin/main

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
        'phone',
        'avatar'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // 🔥 JWT bắt buộc
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
<<<<<<< HEAD
}
=======

    /**
     * Get all courts owned by this user
     * 
     * Pattern: Relationship (HasMany)
     * SOLID: Single Responsibility - Model manages relationships
     * 
     * @return HasMany
     */
    public function courts(): HasMany
    {
        return $this->hasMany(Court::class, 'owner_id');
    }
}
>>>>>>> origin/main
