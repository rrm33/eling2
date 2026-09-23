<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\HasApiTokens;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

#[Fillable(['name', 'email', 'password', 'shop_id', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::addGlobalScope('hideSuperAdminFromLowerRoles', function ($builder) {
            // Use Auth::hasUser() to check in-memory resolved user, preventing infinite recursion
            if (app()->bound('auth') && Auth::hasUser()) {
                $user = Auth::user();
                if ($user && $user->role !== 'super_admin') {
                    $builder->where('users.role', '!=', 'super_admin');
                }
            }
        });
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->role === 'admin' || $this->role === 'super_admin';
    }

    public function getRoleAttribute($value)
    {
        return $value ? strtolower($value) : $value;
    }

    public function setRoleAttribute($value)
    {
        $this->attributes['role'] = $value ? strtolower($value) : $value;
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}
