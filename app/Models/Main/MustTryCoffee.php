<?php

namespace App\Models\Main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\User;

class MustTryCoffee extends Model
{
    use HasFactory;

    protected $table = 'must_try_coffee';

    protected $fillable = [
        'user_id',
        'coffee_id',
        'comment',
    ];

    public function coffee()
    {
        return $this->belongsTo(Coffee::class, 'coffee_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
