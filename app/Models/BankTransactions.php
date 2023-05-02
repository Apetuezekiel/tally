<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankTransactions extends Model
{
    protected $fillables=[
        'username', 'user_id', 'transaction_amount', 'transaction_type', 'transaction_method', 'charges', 'transaction_id', 'source_acct', 'destination_acct', 'destination_bank'
    ];
}
