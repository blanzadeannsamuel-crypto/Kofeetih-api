<?php

namespace App\Models\Main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Coffee extends Model
{
    use HasFactory;
    protected $table = 'coffees';

    protected $fillable = [
        'coffee_name',
        'image_url',
        'description',
        'ingredients',
        'coffee_type',
        'lactose',
        'minimum_price',
        'maximum_price',
        'rating',
        'likes',
        'favorites',
    ];

     public function likedByUsers()
    {
        return $this->belongsToMany(\App\Models\User::class, 'coffee_likes');
    }
    
    public function favoritedByUsers()
    {
        return $this->belongsToMany(\App\Models\User::class, 'coffee_favorites');
    }

    public function ratings()
    {
        return $this->hasMany(\App\Models\Main\CoffeeRating::class);
    }


    // Helper functions
    public function isLikedBy(User $user)
    {
        return $this->likedByUsers()->where('user_id', $user->id)->exists();
    }

    public function isFavoritedBy(User $user)
    {
        return $this->favoritedByUsers()->where('user_id', $user->id)->exists();
    }
}
