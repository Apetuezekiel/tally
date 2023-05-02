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
        Schema::create('pending_transaction', function (Blueprint $table) {
            $table->id();
            $table->string('tally_account');
            $table->string('amount');
            $table->string('dest_account');
            $table->string('dest_bank');
            $table->string('trans_ref');
            $table->string('processed');
            $table->string('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_transaction');
    }
};
