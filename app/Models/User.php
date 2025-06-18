<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Hash;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users'; // Nama tabel

    protected $primaryKey = 'id_user'; // Primary key

    public $timestamps = false; // Karena tidak pakai default timestamps Laravel

    protected $fillable = [
        'email', 'name','phone','role', 'image', 'password',
        'status', 'reason', 'blocked_date', 'blocked_by',
        'created_by', 'created_at','updated_at', 'deleted', 'deleted_by', 'deleted_date'
    ];

    protected $hidden = ['password'];

    public function setPasswordAttribute($password)
    {
        $this->attributes['password'] = Hash::make($password);
    }

     public function userVectors()
    {
        return $this->hasMany(UserVector::class,'id_user','id_user');
    }

}
