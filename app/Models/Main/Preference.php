<?php

namespace App\Models\Main;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Preference extends Model
{
    protected $fillable  = [
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

    public function user(){
        return $this->belongsTo(User::class);
    }
}
