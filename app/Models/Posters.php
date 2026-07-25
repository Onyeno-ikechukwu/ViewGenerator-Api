<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Posters extends Model
{
    protected $fillable = [
        'user_id', 
        'category_id', 
        'category', 
        'video_url', 
        'music_url', 
        'payment_package', 
        'amount', 
        'status'
    ];
}
