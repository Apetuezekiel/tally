<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PendingTrans extends Model
{
    protected $fillables=[
        'tally_account', 'amount', 'dest_account', 'dest_bank', 'trans_ref', 'processed', 'status'
    ];
}
