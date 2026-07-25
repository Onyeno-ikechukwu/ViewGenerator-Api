<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class payment extends Model
{
    protected $table = 'payment';

    protected $fillable = [
        'user_id',
        'tx_ref',
        'transaction_id',
        'amount',
        'currency',
        'status',
        'plan',
    ];
}
