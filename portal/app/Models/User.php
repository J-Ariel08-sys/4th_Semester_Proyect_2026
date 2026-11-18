<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    public function isAdministrator(): bool
    {
        return $this->role === 'administrador';
    }

    public function managesAcademics(): bool
    {
        return in_array($this->role, ['administrador', 'coordinador'], true);
    }

    public function hasAccessApproval(): bool
    {
        return $this->isAdministrator() || ($this->access_approved_at !== null && ($this->role !== 'interno' || $this->eligible));
    }

    public function canSignIn(): bool
    {
        return ! $this->trashed() && $this->active && $this->activated_at !== null && $this->hasAccessApproval();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'activated_at' => 'datetime',
            'access_approved_at' => 'datetime',
            'security_version' => 'integer',
            'password' => 'hashed',
            'active' => 'boolean',
            'eligible' => 'boolean',
        ];
    }
}
