<?php

namespace App\Models;

use App\Models\Concerns\MencatatAktivitas;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'username', 'email', 'password', 'role', 'nik', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, MencatatAktivitas, Notifiable;

    public const ROLES = [
        'admin' => 'Admin Desa',
        'kades' => 'Kepala Desa',
        'warga' => 'Warga',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isKades(): bool
    {
        return $this->role === 'kades';
    }

    public function isWarga(): bool
    {
        return $this->role === 'warga';
    }

    public function roleLabel(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }

    /** Nama rute dashboard sesuai role. */
    public function homeRoute(): string
    {
        return match ($this->role) {
            'admin' => 'admin.dashboard',
            'kades' => 'kades.dashboard',
            default => 'warga.beranda',
        };
    }
}
