<?php

namespace App\Models\Main;

use Illuminate\Database\Eloquent\Model;
use App\Models\Main\Coffee;
use App\Models\User;

class CoffeeRating extends Model
{
    protected $fillable = ['user_id','coffee_id','rating'];

    public function coffee()
    {
        return $this->belongsTo(Coffee::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
