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
            $table->index(['order_status', 'created_at']);
        });

        Schema::table('customers_orders', function (Blueprint $table) {
            $table->index(['customer_id', 'order_id']);
        });

        Schema::table('order_details', function (Blueprint $table) {
            $table->index(['customer_order_id', 'parent_id']);
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['order_status', 'created_at']);
        });

        Schema::table('customers_orders', function (Blueprint $table) {
            $table->dropIndex(['customer_id', 'order_id']);
        });

        Schema::table('order_details', function (Blueprint $table) {
            $table->dropIndex(['customer_order_id', 'parent_id']);
            $table->dropIndex(['product_id']);
        });
    }
};
