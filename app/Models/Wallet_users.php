<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet_users extends Model
{
    protected $fillables=[
        'username', 'phone_no', 'verified'
    ];
}
