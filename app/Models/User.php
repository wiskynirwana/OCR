<?php

namespace App\Models;

use App\Notifications\VerifyEmailCode;
use App\Services\VerificationCodeService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
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
     * Kirim kode verifikasi 6 digit (menggantikan link bawaan Laravel).
     * Dipanggil otomatis oleh event Registered dan tombol kirim ulang.
     */
    public function sendEmailVerificationNotification(): void
    {
        $code = app(VerificationCodeService::class)->generate('email_verify', $this->email);
        $this->notify(new VerifyEmailCode($code));
    }
}
