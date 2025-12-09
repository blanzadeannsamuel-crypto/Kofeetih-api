<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Main\Coffee;
use App\Models\Main\Preference;
use App\Models\Main\Like;
use Carbon\Carbon;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $primaryKey = 'id';

    protected $fillable = [
        'last_name',
        'first_name',
        'display_name',
        'birthdate',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'birthdate' => 'date',
    ];

    protected $dates = [
        'birthdate',
    ];

    public $timestamps = true;

    // =======================
    // Relations
    // =======================
    public function likes()
    {
        return $this->belongsToMany(
            Coffee::class,
            'coffee_likes',
            'user_id',
            'coffee_id'
        )->withTimestamps()->using(Like::class);
    }

    public function favorites()
    {
        return $this->belongsToMany(
            Coffee::class,
            'coffee_favorites',
            'user_id',
            'coffee_id'
        )->withTimestamps();
    }

    public function ratings()
    {
        return $this->belongsToMany(
            Coffee::class,
            'coffee_ratings',
            'user_id',
            'coffee_id'
        )->withPivot('rating')->withTimestamps();
    }

    public function preference()
    {
        return $this->hasOne(Preference::class, 'user_id');
    }

    public function getAgeAttribute()
    {
        if (!$this->birthdate) return null;
        return Carbon::parse($this->birthdate)->age;
    }
}
