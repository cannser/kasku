<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    /*
     * MASS ASSIGNMENT PROTECTION: hanya field ini yang boleh diisi lewat create()/fill().
     * "role" dan "is_active" sengaja TIDAK ada di sini, jadi walau penyerang menambahkan
     * role=admin di form registrasi, nilainya tidak akan pernah tersimpan.
     */
    protected $fillable = ['username', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password'  => 'hashed',   // otomatis di-hash (bcrypt) saat diisi
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
