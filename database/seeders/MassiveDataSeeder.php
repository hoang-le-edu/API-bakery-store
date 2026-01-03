<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MassiveDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Target: Generate > 5GB of data
     * Estimate: 
     * - 5 million products (~1.5GB)
     * - 20 million category_product (~3GB)
     * - 30 million products_toppings (~4.5GB)
     * Total: ~9GB
     */
    public function run()
    {
        $this->command->info('Starting massive data generation...');
        $startTime = microtime(true);

        // Disable query log to save memory
        DB::connection()->disableQueryLog();
        
        // Disable foreign key checks temporarily
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        
        // Create categories if not exist
        $this->command->info('Ensuring categories exist...');
        $categoryIds = $this->ensureCategories();

        // Base product templates
        $productTemplates = [
            ['name' => 'Rau câu dừa mini 100gr', 'image' => 'Product/RauCauDua1.png', 'price' => 6000],
            ['name' => 'Rau câu cà phê mini 100gr', 'image' => 'Product/RauCauCafeDua.png', 'price' => 6000],
            ['name' => 'Rau câu lá dứa mini 100gr', 'image' => 'Product/RauCauCafeDua.png', 'price' => 6000],
            ['name' => 'Rau câu dừa hoa lá', 'image' => 'Product/RauCauHoaLaDua.png', 'price' => 30000],
            ['name' => 'Rau câu cà phê hoa lá', 'image' => 'Product/RauCauHoaLaCafe.png', 'price' => 30000],
            ['name' => 'Rau câu lá dứa hoa lá', 'image' => 'Product/RauCauHoaLaCafeDua1.png', 'price' => 30000],
            ['name' => 'Rau câu ngẫu nhiên hoa lá', 'image' => 'Product/DaDang3.png', 'price' => 30000],
            ['name' => 'Rau câu dừa khổng lồ 600gr', 'image' => '', 'price' => 39000],
            ['name' => 'Rau câu cà phê khổng lồ 600gr', 'image' => '', 'price' => 39000],
            ['name' => 'Rau câu lá dứa khổng lồ 600gr', 'image' => '', 'price' => 39000],
            ['name' => 'Rau câu sơn thuỷ khổng lồ 600gr', 'image' => 'Product/RauCauSonThuy2.png', 'price' => 43000],
        ];

        // Topping templates
        $toppingTemplates = [
            ['name' => 'Bánh plan', 'price' => 0],
            ['name' => 'Hạt sen', 'price' => 0],
            ['name' => 'Nhãn', 'price' => 0],
            ['name' => 'Sợi dừa', 'price' => 0],
        ];

        // Generate toppings first (100,000 toppings)
        $this->command->info('Generating toppings...');
        $toppingIds = $this->generateToppings($toppingTemplates, 100000);
        
        // Generate products in batches (5,000,000 products)
        $this->command->info('Generating products...');
        $totalProducts = 5000000; // 5 million products
        $batchSize = 1000;
        $productIds = [];

        for ($i = 0; $i < $totalProducts; $i += $batchSize) {
            $batch = $this->generateProductBatch($productTemplates, $i, $batchSize);
            $productIds = array_merge($productIds, $batch);
            
            if (($i + $batchSize) % 10000 == 0) {
                $this->command->info("Generated " . ($i + $batchSize) . " products...");
            }
        }

        // Generate category_product relationships
        $this->command->info('Generating category_product relationships...');
        $this->generateCategoryProduct($productIds, $categoryIds, $batchSize);

        // Generate products_toppings relationships
        $this->command->info('Generating products_toppings relationships...');
        $this->generateProductToppings($productIds, $toppingIds, $batchSize);

        // Re-enable foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        $this->command->info("Massive data generation completed in {$duration} seconds!");
        
        // Calculate approximate database size
        $dbSize = $this->getDatabaseSize();
        $this->command->info("Approximate database size: {$dbSize}");
    }

    private function ensureCategories()
    {
        $categories = [
            [
                'id' => '23036a25-b572-11ef-8332-70a6cc37ddf9',
                'name' => 'Đồ trang trí đi kèm',
                'description' => null,
                'type' => 'Other',
                'priority' => 0,
                'show_for_customer' => 0,
            ],
            [
                'id' => '2a1dbe25-b549-11ef-8332-70a6cc37ddf9',
                'name' => 'Topping',
                'description' => 'Topping rau câu',
                'type' => 'Topping',
                'priority' => 0,
                'show_for_customer' => 0,
            ],
            [
                'id' => '2a1dbe77-b549-11ef-8332-70a6cc37ddf9',
                'name' => 'Rau câu mini',
                'description' => null,
                'type' => 'Food',
                'priority' => 1,
                'show_for_customer' => 1,
            ],
            [
                'id' => '9b577267-b54a-11ef-8332-70a6cc37ddf9',
                'name' => 'Rau câu hoa lá',
                'description' => null,
                'type' => 'Food',
                'priority' => 1,
                'show_for_customer' => 1,
            ],
            [
                'id' => '9b5774ed-b54a-11ef-8332-70a6cc37ddf9',
                'name' => 'Rau câu khổng lồ',
                'description' => null,
                'type' => 'Food',
                'priority' => 1,
                'show_for_customer' => 1,
            ],
            [
                'id' => '9b5774er-b54a-11ef-8332-70a6cc37ddf9',
                'name' => 'Rau câu sinh nhật',
                'description' => null,
                'type' => 'Food',
                'priority' => 1,
                'show_for_customer' => 1,
            ],
            [
                'id' => '9b5774ez-b54a-11ef-8332-70a6cc37ddf9',
                'name' => 'Rau câu lễ hội',
                'description' => null,
                'type' => 'Food',
                'priority' => 1,
                'show_for_customer' => 1,
            ],
            [
                'id' => '9b577593-b54a-11ef-8332-70a6cc37ddf9',
                'name' => 'Combo',
                'description' => null,
                'type' => 'Food',
                'priority' => 1,
                'show_for_customer' => 0,
            ],
        ];

        $categoryIds = [];
        foreach ($categories as $category) {
            // Insert or update category
            DB::table('categories')->updateOrInsert(
                ['id' => $category['id']],
                [
                    'name' => $category['name'],
                    'description' => $category['description'],
                    'type' => $category['type'],
                    'priority' => $category['priority'],
                    'show_for_customer' => $category['show_for_customer'],
                    'created_at' => now(),
                    'updated_at' => now(),
                    'deleted_at' => null,
                ]
            );
            $categoryIds[] = $category['id'];
        }

        $this->command->info('Categories ensured: ' . count($categoryIds));
        return $categoryIds;
    }

    private function generateToppings($templates, $count)
    {
        $toppingIds = [];
        $batchSize = 1000;
        $batches = ceil($count / $batchSize);
        $timestamp = time();

        for ($batch = 0; $batch < $batches; $batch++) {
            $data = [];
            $currentBatchSize = min($batchSize, $count - ($batch * $batchSize));

            for ($i = 0; $i < $currentBatchSize; $i++) {
                $index = ($batch * $batchSize) + $i;
                $template = $templates[$index % count($templates)];
                $uuid = (string) Str::uuid();
                $toppingIds[] = $uuid;

                $data[] = [
                    'id' => $uuid,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'name' => $template['name'] . ' ' . $timestamp . '_' . $index,
                    'description' => null,
                    'image' => null,
                    'status' => 'active',
                    'price' => $template['price'],
                    'cost' => 0,
                    'up_m_price' => 0,
                    'up_l_price' => 0,
                    'is_topping' => 1,
                    'priority' => 0,
                    'created_by' => '1',
                    'deleted_at' => null,
                ];
            }

            DB::table('products')->insert($data);
            
            if (($batch + 1) % 10 == 0) {
                $this->command->info("Generated " . (($batch + 1) * $batchSize) . " toppings...");
            }
        }

        return $toppingIds;
    }

    private function generateProductBatch($templates, $startIndex, $batchSize)
    {
        $data = [];
        $productIds = [];
        $timestamp = time();

        for ($i = 0; $i < $batchSize; $i++) {
            $index = $startIndex + $i;
            $template = $templates[$index % count($templates)];
            $uuid = (string) Str::uuid();
            $productIds[] = $uuid;

            $data[] = [
                'id' => $uuid,
                'created_at' => now(),
                'updated_at' => now(),
                'name' => $template['name'] . ' ' . $timestamp . '_' . $index,
                'description' => null,
                'image' => $template['image'],
                'status' => 'active',
                'price' => $template['price'],
                'cost' => 0,
                'up_m_price' => 0,
                'up_l_price' => 0,
                'is_topping' => 0,
                'priority' => 1,
                'created_by' => '1',
                'deleted_at' => null,
            ];
        }

        DB::table('products')->insert($data);
        return $productIds;
    }

    private function generateCategoryProduct($productIds, $categoryIds, $batchSize)
    {
        $totalProducts = count($productIds);
        
        for ($i = 0; $i < $totalProducts; $i += $batchSize) {
            $data = [];
            $currentBatchSize = min($batchSize, $totalProducts - $i);

            for ($j = 0; $j < $currentBatchSize; $j++) {
                $productId = $productIds[$i + $j];
                // Each product belongs to 2-4 random categories
                $numCategories = rand(2, 4);
                $selectedCategories = array_rand(array_flip($categoryIds), $numCategories);
                
                if (!is_array($selectedCategories)) {
                    $selectedCategories = [$selectedCategories];
                }

                foreach ($selectedCategories as $categoryId) {
                    $data[] = [
                        'category_id' => $categoryId,
                        'product_id' => $productId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            DB::table('category_product')->insert($data);
            
            if (($i + $batchSize) % 10000 == 0) {
                $this->command->info("Generated category_product for " . ($i + $batchSize) . " products...");
            }
        }
    }

    private function generateProductToppings($productIds, $toppingIds, $batchSize)
    {
        $totalProducts = count($productIds);
        $totalToppings = count($toppingIds);
        
        for ($i = 0; $i < $totalProducts; $i += $batchSize) {
            $data = [];
            $currentBatchSize = min($batchSize, $totalProducts - $i);

            for ($j = 0; $j < $currentBatchSize; $j++) {
                $productId = $productIds[$i + $j];
                $numToppings = min(rand(3, 6), $totalToppings);
                
                // Get unique random toppings
                $selectedToppings = array_rand(array_flip($toppingIds), $numToppings);
                if (!is_array($selectedToppings)) {
                    $selectedToppings = [$selectedToppings];
                }
                
                foreach ($selectedToppings as $toppingId) {
                    $extraPrice = [1000, 2000, 3000, 5000][rand(0, 3)];

                    $data[] = [
                        'product_id' => $productId,
                        'topping_id' => $toppingId,
                        'extra_price' => $extraPrice,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            DB::table('products_toppings')->insert($data);
            
            if (($i + $batchSize) % 10000 == 0) {
                $this->command->info("Generated products_toppings for " . ($i + $batchSize) . " products...");
            }
        }
    }

    private function getDatabaseSize()
    {
        $result = DB::select("
            SELECT 
                table_schema AS 'Database',
                ROUND(SUM(data_length + index_length) / 1024 / 1024 / 1024, 2) AS 'Size (GB)'
            FROM information_schema.TABLES
            WHERE table_schema = DATABASE()
            GROUP BY table_schema
        ");

        return $result[0]->{'Size (GB)'} . ' GB';
    }
}

