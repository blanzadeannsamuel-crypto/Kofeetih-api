<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLogsModel extends Model
{
    protected $table = 'audit_logs';

    protected $fillable = [
        'user_id',
        'action',
        'description',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
