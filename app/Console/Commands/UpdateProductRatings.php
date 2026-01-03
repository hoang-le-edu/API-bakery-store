<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;

class UpdateProductRatings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:update-ratings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update cached avg_rating and review_count for all products based on existing reviews';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to update product ratings...');

        $products = Product::all();
        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        $updatedCount = 0;

        foreach ($products as $product) {
            $product->updateRatingCache();
            $updatedCount++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Successfully updated ratings for {$updatedCount} products.");

        return Command::SUCCESS;
    }
}
