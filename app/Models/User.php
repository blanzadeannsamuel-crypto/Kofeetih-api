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

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $primaryKey = 'id';

    protected $fillable = [
        'last_name',
        'first_name',
        'display_name',
        'age',
        'email',
        'password',
        'role',
        'status',          // Added for active/inactive/pending_deletion
        'last_login_at',   // Added to track last login for inactivity
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'last_login_at' => 'datetime', // Cast last login to datetime
    ];

    protected $dates = [
        'deleted_at',
        'pending_delete_at',
        'archived_at',
        'last_login_at',
    ];

    public $timestamps = true;

    // Relations
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
}
