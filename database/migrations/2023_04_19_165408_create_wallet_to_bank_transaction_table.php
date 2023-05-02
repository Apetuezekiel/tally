<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wallet_to_bank_transaction', function (Blueprint $table) {
            $table->id();
            $table->string('username');
            $table->string('user_id');
            $table->double('transaction_amount');
            $table->string('transaction_type');
            $table->string('transaction_method');
            $table->double('charges');
            $table->string('transaction_id');
            $table->string('source_acct');
            $table->string('destination_acct');
            $table->string('destination_bank');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_to_bank_transaction');
    }
};
