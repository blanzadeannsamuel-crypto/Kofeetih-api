<?php

namespace App\Models\Main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Preference extends Model
{
    use HasFactory, SoftDeletes;


    protected $table = 'preferences';

    protected $fillable = [
        'user_id',  
        'coffee_type',
        'coffee_allowance',
        'serving_temp',
        'lactose',
        'nuts_allergy',
    ];

    protected $casts = [
        'coffee_allowance' => 'integer',
        'lactose' => 'boolean',
        'nuts_allergy' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
