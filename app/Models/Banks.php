<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banks extends Model
{
    protected $fillables=[
        'bankCode', 'bankName'
    ];
}
