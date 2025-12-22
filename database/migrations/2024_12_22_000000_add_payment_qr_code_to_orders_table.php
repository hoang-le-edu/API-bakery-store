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
        Schema::table('orders', function (Blueprint $table) {
            $table->text('payment_qr_code')->nullable()->after('payment_link');
            $table->string('payment_account_number')->nullable()->after('payment_qr_code');
            $table->string('payment_account_name')->nullable()->after('payment_account_number');
            $table->string('payment_link_id')->nullable()->after('payment_account_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_qr_code', 'payment_account_number', 'payment_account_name', 'payment_link_id']);
        });
    }
};
