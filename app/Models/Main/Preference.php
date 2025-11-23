<?php

namespace App\Models\Main;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Preference extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'coffee_type',
        'coffee_allowance',
        'temp',
        'lactose',
        'nuts_allergy',
    ];

    protected $casts = [
        'coffee_allowance' => 'integer',
        'lactose' => 'boolean',
        'nuts_allergy' => 'boolean',
    ];

    /**
     * Relationship to User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
