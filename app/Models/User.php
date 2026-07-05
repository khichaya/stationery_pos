<?php

namespace App\Models;

use database\Factories\UserFactory;
use Illuminate\database\Eloquent\Attributes\Fillable;
use Illuminate\database\Eloquent\Attributes\Hidden;
use Illuminate\database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// 🟢 تحديث الـ Fillable لإضافة الحقول الجديدة وتطهير role_id القديم
#[Fillable(['name', 'email', 'password', 'role', 'permissions'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

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
            'permissions' => 'array', // 👈 تحويل الـ JSON تلقائياً لمصفوفة PHP ذكية!
        ];
    }
}