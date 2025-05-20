<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $table = 'user';

    protected $primaryKey = 'id_user';

    protected $keyType = 'string';

    public $timestamps = false;

    public $incrementing = false;


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = [];

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
        'email_verified_at' => 'datetime'
    ];

    /**
     * The attributes that should be appended.
     *
     * @var array<string>
     */
    public function getAttributeValue($key)
    {
        if ($key == 'name') {
            return $this->getUserName();
        }

        return parent::getAttributeValue($key);
    }

    /**
     * The attributes that should be appended.
     *
     * @var array<string>
     */
    public function getUserName()
    {
        return $this->detail->nama ?? $this->id_user;
    }


    /**
     * Get the user's detail
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     * */
    public function detail()
    {
        return $this->hasOne(Pegawai::class, 'nik', 'id_user');
    }
}
