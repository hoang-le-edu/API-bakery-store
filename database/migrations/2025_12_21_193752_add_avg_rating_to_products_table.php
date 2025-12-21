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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('avg_rating', 3, 2)->default(0.00)->after('priority');
            $table->integer('review_count')->default(0)->after('avg_rating');

            // Add index for performance
            $table->index('avg_rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['avg_rating']);
            $table->dropColumn(['avg_rating', 'review_count']);
        });
    }
};
