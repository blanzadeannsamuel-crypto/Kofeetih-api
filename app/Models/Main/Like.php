<?php

namespace App\Models\Main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Like extends Model
{
    use HasFactory;

    protected $table = 'coffee_likes';

    protected $fillable = [
        'user_id',
        'coffee_id',
    ];

    protected $casts = [
        'user_id'   => 'integer',
        'coffee_id' => 'integer',
    ];
    
    public function coffee() { return $this->belongsTo(Coffee::class, 'coffee_id'); }
    public function user() { return $this->belongsTo(User::class); }
}
