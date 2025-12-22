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
        Schema::create('transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payer_wallet_id')->constrained('wallets')->onDelete('cascade');
            $table->foreignId('payee_wallet_id')->constrained('wallets')->onDelete('cascade');
            $table->decimal('value', 15, 2);
            $table->timestamps();

            $table->index('payer_wallet_id');
            $table->index('payee_wallet_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transfers');
    }
};
