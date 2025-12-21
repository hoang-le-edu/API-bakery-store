<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Voucher;
use App\Models\User;
use Illuminate\Support\Str;
use Carbon\Carbon;

class VoucherSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get first admin user as creator
        $adminUser = User::first();

        if (!$adminUser) {
            $this->command->error('No users found. Please create a user first.');
            return;
        }

        $now = Carbon::now();
        $endDate = $now->copy()->addMonths(3); // Valid for 3 months

        // Create 20 vouchers with 70% discount
        $this->command->info('Creating 20 vouchers with 70% discount...');
        for ($i = 1; $i <= 20; $i++) {
            Voucher::create([
                'vourcher_code' => 'SUPER70-' . strtoupper(Str::random(6)),
                'status' => 'active',
                'start_date' => $now,
                'end_date' => $endDate,
                'discount_type' => 'percent',
                'discount_amount' => 0,
                'discount_percent' => 70.00,
                'limit' => 100, // Each voucher can be used 100 times
                'minimum' => 100000, // Minimum order 100k VND
                'limit_per_order' => 200000, // Max discount 200k per order
                'apply_type' => 'discount',
                'config' => json_encode(['description' => '70% discount voucher - Super Sale']),
                'created_by' => $adminUser->id,
            ]);
        }

        // Create 30 vouchers with 50% discount
        $this->command->info('Creating 30 vouchers with 50% discount...');
        for ($i = 1; $i <= 30; $i++) {
            Voucher::create([
                'vourcher_code' => 'MEGA50-' . strtoupper(Str::random(6)),
                'status' => 'active',
                'start_date' => $now,
                'end_date' => $endDate,
                'discount_type' => 'percent',
                'discount_amount' => 0,
                'discount_percent' => 50.00,
                'limit' => 150, // Each voucher can be used 150 times
                'minimum' => 50000, // Minimum order 50k VND
                'limit_per_order' => 150000, // Max discount 150k per order
                'apply_type' => 'discount',
                'config' => json_encode(['description' => '50% discount voucher - Mega Sale']),
                'created_by' => $adminUser->id,
            ]);
        }

        // Create 50 vouchers with 20% discount
        $this->command->info('Creating 50 vouchers with 20% discount...');
        for ($i = 1; $i <= 50; $i++) {
            Voucher::create([
                'vourcher_code' => 'SAVE20-' . strtoupper(Str::random(6)),
                'status' => 'active',
                'start_date' => $now,
                'end_date' => $endDate,
                'discount_type' => 'percent',
                'discount_amount' => 0,
                'discount_percent' => 20.00,
                'limit' => 200, // Each voucher can be used 200 times
                'minimum' => 30000, // Minimum order 30k VND
                'limit_per_order' => 100000, // Max discount 100k per order
                'apply_type' => 'discount',
                'config' => json_encode(['description' => '20% discount voucher - Daily Sale']),
                'created_by' => $adminUser->id,
            ]);
        }

        // Create some shipping vouchers as bonus
        $this->command->info('Creating 10 FREE SHIPPING vouchers...');
        for ($i = 1; $i <= 10; $i++) {
            Voucher::create([
                'vourcher_code' => 'FREESHIP-' . strtoupper(Str::random(6)),
                'status' => 'active',
                'start_date' => $now,
                'end_date' => $endDate,
                'discount_type' => 'fixed',
                'discount_amount' => 30000, // Free 30k shipping
                'discount_percent' => 0,
                'limit' => 100,
                'minimum' => 0, // No minimum
                'limit_per_order' => 30000,
                'apply_type' => 'shipping_fee',
                'config' => json_encode(['description' => 'Free shipping voucher']),
                'created_by' => $adminUser->id,
            ]);
        }

        $this->command->info('✅ Successfully created 110 vouchers!');
        $this->command->info('- 20 vouchers: 70% discount (SUPER70-XXXXXX)');
        $this->command->info('- 30 vouchers: 50% discount (MEGA50-XXXXXX)');
        $this->command->info('- 50 vouchers: 20% discount (SAVE20-XXXXXX)');
        $this->command->info('- 10 vouchers: Free shipping (FREESHIP-XXXXXX)');
    }
}
