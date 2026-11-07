<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppNotification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    protected $fillable = ['user_id', 'title', 'message', 'link', 'is_read', 'read_at'];

    protected $casts = ['is_read' => 'boolean', 'read_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }
}
