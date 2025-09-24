<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'phone',
        'is_active',
        'last_login_at',
        'email_verified_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean'
    ];


    public function projects(){
        return $this->hasMany(Project::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function twoFactorAuth()
    {
        return $this->hasOne(TwoFactorAuth::class);
    }

    public function hasRole($role)
    {
        if (is_string($role)) {
            return $this->roles()->where('roles.slug', $role)->exists();
        }
        return $this->roles()->where('roles.id', $role)->exists();
    }

    public function hasPermission($permission)
    {
        return $this->roles()->get()->pluck('permissions')->flatten()->contains($permission);
    }

    public function assignRole($role)
    {
        if (is_string($role)) {
            $role = Role::where('slug', $role)->first();
        }
        
        if ($role && !$this->hasRole($role->id)) {
            $this->roles()->attach($role->id);
        }
    }

    public function removeRole($role)
    {
        if (is_string($role)) {
            $role = Role::where('slug', $role)->first();
        }
        
        if ($role) {
            $this->roles()->detach($role->id);
        }
    }

    public function enable2FA()
    {
        if (!$this->twoFactorAuth) {
            $this->twoFactorAuth()->create([
                'secret_key' => bin2hex(random_bytes(16)),
                'is_enabled' => true
            ]);
        } else {
            $this->twoFactorAuth->update(['is_enabled' => true]);
        }
        
        return $this->twoFactorAuth->generateBackupCodes();
    }

    public function disable2FA()
    {
        if ($this->twoFactorAuth) {
            $this->twoFactorAuth->update(['is_enabled' => false]);
        }
    }
}
