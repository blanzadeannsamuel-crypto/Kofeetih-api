<?php

namespace App\Models\Main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Coffee extends Model
{
    use HasFactory;

    protected $primaryKey = 'coffee_id';

    protected $fillable = [
        'coffee_name',
        'coffee_image',
        'coffee_type',
        'description',
        'ingredients',
        'serving_temp',
        'nuts',
        'lactose',
        'price',
        'rating',
        'likes',
        'favorites',
    ];

    // fixed property name and types
    protected $casts = [
        'rating' => 'float',
        'likes' => 'integer',
        'favorites' => 'integer',
        'price' => 'float',
    ];

    public function likedBy() {
        return $this->belongsToMany(User::class, 'coffee_likes', 'coffee_id', 'user_id')->withTimestamps();
    }

    public function favoritedBy() {
        return $this->belongsToMany(User::class, 'coffee_favorites', 'coffee_id', 'user_id')->withTimestamps();
    }

    public function ratings() {
        return $this->hasMany(CoffeeRating::class, 'coffee_id');
    }

    public function getTotalLikesAttribute()
    {
        return (intval($this->likes ?? 0) + intval($this->liked_by_count ?? 0));
    }

    public function getTotalFavoritesAttribute()
    {
        // fixed variable name
        return (intval($this->favorites ?? 0) + intval($this->favorited_by_count ?? 0));
    }

    public function getFinalAverageRatingAttribute()
    {
        $seededAvg = (float) ($this->rating ?? 0);
        $seededCount = 1;

        $agg = $this->ratings()->selectRaw('AVG(rating) as avg_rating, COUNT(*) as count')->first();
        $dynamicAvg = $agg->avg_rating ?? 0;
        $dynamicCount = $agg->count ?? 0;

        $totalCount = $seededCount + $dynamicCount;
        $totalSum = ($seededAvg * $seededCount) + ($dynamicAvg * $dynamicCount);

        return $totalCount > 0 ? round($totalSum / $totalCount, 1) : 0;
    }
}
