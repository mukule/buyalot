<?php

namespace Database\Seeders;

use App\Models\Customer\Customer;
use App\Models\User;
use DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $testCustomers = [
            [
                'customer_code' => 'CUST001',
                'first_name' => 'Steven',
                'last_name' => 'Maina',
                'email' => 'stevenmaina17@gmail.com',
                'phone' => '+254710767015',
                'date_of_birth' => '1999-05-15',
                'gender' => 'male',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'status' => 'active',
            ],
            [
                'customer_code' => 'CUST-JANE02',
                'first_name' => 'Jane',
                'last_name' => 'Smith',
                'email' => 'jane.smith.test@outlook.com',
                'phone' => '+254723456789',
                'date_of_birth' => '1985-08-22',
                'gender' => 'female',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'status' => 'active',
            ],
            [
                'customer_code' => 'CUST-MICH03',
                'first_name' => 'Michael',
                'last_name' => 'Johnson',
                'email' => 'michael.johnson.test@yahoo.com',
                'phone' => '+254734567890',
                'date_of_birth' => '1992-12-03',
                'gender' => 'male',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'status' => 'active',
            ],
            [
                'customer_code' => 'CUST-SARA04',
                'first_name' => 'Sarah',
                'last_name' => 'Wilson',
                'email' => 'sarah.wilson.test@gmail.com',
                'phone' => '+254745678901',
                'date_of_birth' => '1988-04-18',
                'gender' => 'female',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'status' => 'active',
            ],
            [
                'customer_code' => 'CUST-DAVI05',
                'first_name' => 'David',
                'last_name' => 'Brown',
                'email' => 'david.brown.test@hotmail.com',
                'phone' => '+254756789012',
                'date_of_birth' => '1995-09-10',
                'gender' => 'male',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'status' => 'active',
            ],
            [
                'customer_code' => 'CUST-EMMA06',
                'first_name' => 'Emma',
                'last_name' => 'Davis',
                'email' => 'emma.davis.test@gmail.com',
                'phone' => '+254767890123',
                'date_of_birth' => '1991-01-28',
                'gender' => 'female',
                'password' => Hash::make('password123'),
                'status' => 'inactive',
            ],
            [
                'customer_code' => 'CUST-JAME07',
                'first_name' => 'James',
                'last_name' => 'Miller',
                'email' => 'james.miller.test@outlook.com',
                'phone' => '+254778901234',
                'date_of_birth' => '1987-07-14',
                'gender' => 'male',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'status' => 'suspended',
            ],
            [
                'customer_code' => 'CUST-LISA08',
                'first_name' => 'Lisa',
                'last_name' => 'Anderson',
                'email' => 'lisa.anderson.test@yahoo.com',
                'phone' => '+254789012345',
                'date_of_birth' => '1993-11-25',
                'gender' => 'female',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'status' => 'active',
            ],
            [
                'customer_code' => 'CUST-ROBE09',
                'first_name' => 'Robert',
                'last_name' => 'Taylor',
                'email' => 'robert.taylor.test@gmail.com',
                'phone' => '+254790123456',
                'date_of_birth' => '1989-03-07',
                'gender' => 'male',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'status' => 'active',
            ],
            [
                'customer_code' => 'CUST-MARI10',
                'first_name' => 'Maria',
                'last_name' => 'Garcia',
                'email' => 'maria.garcia.test@hotmail.com',
                'phone' => '+254701234567',
                'date_of_birth' => '1994-06-12',
                'gender' => 'female',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'status' => 'active',
            ]
        ];

        foreach ($testCustomers as $data) {
            // 1. Create or update the user
            $user = User::updateOrCreate(
                ['email' => $data['email']], // unique by email
                [
                    'name' => $data['first_name'].' '.$data['last_name'],
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                    'phone' => $data['phone'],
                    'status' => $data['status'] === 'active',
                    'email_verified_at' => now(),
                    'user_type' => 'customer',
                ]
            );

            // 2. Create or update the customer and attach the user
            $customer = Customer::updateOrCreate(
                ['customer_code' => $data['customer_code']],
                [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'date_of_birth' => $data['date_of_birth'],
                    'gender' => $data['gender'],
//                    'password' => Hash::make($data['password']),
                    'status' => $data['status'] === 'active',
//                    'email_verified_at' => now(),
                    'user_id' => $user->id, // attach user_id
                ]
            );

            // create related records
            $this->createCustomerAddress($customer);
            $this->createMarketingPreferences($customer);
            $this->createCustomerStatistics($customer);

            $this->command->info("Created user + customer: {$customer->first_name} {$customer->last_name}");
        }

        // Generate additional random customers using factory if it exists
        try {
            if (class_exists('\Database\Factories\CustomerFactory')) {
                Customer::factory(40)->create();
                $this->command->info("Created 40 additional customers using factory");
            }
        } catch (\Exception $e) {
            $this->command->warn("Could not create factory customers: " . $e->getMessage());
        }

        $totalCustomers = Customer::count();
        $this->command->info("Total customers created: {$totalCustomers}");
    }

    /**
     * Create default address for customer
     */
    private function createCustomerAddress($customer): void
    {
        $kenyanLocations = [
            ['city' => 'Nairobi', 'state' => 'Nairobi County', 'postal' => '00100'],
            ['city' => 'Mombasa', 'state' => 'Mombasa County', 'postal' => '80100'],
            ['city' => 'Kisumu', 'state' => 'Kisumu County', 'postal' => '40100'],
            ['city' => 'Nakuru', 'state' => 'Nakuru County', 'postal' => '20100'],
            ['city' => 'Eldoret', 'state' => 'Uasin Gishu County', 'postal' => '30100'],
        ];

        $location = $kenyanLocations[array_rand($kenyanLocations)];

        DB::table('customer_addresses')->insert([
            'customer_id' => $customer->id,
            'type' => 'both',
            'label' => 'Home',
            'first_name' => $customer->first_name,
            'last_name' => $customer->last_name,
            'address_line_1' => rand(100, 999) . ' ' . ['Main', 'Oak', 'First', 'Second'][array_rand(['Main', 'Oak', 'First', 'Second'])] . ' Street',
            'address_line_2' => rand(0, 1) ? 'Apartment ' . rand(1, 20) : null,
            'city' => $location['city'],
            'state_province' => $location['state'],
            'postal_code' => $location['postal'],
            'country_code' => 'KE',
            'country_name' => 'Kenya',
            'phone' => $customer->phone,
            'is_default' => true,
            'is_validated' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Create marketing preferences for customer
     */
    private function createMarketingPreferences($customer): void
    {
        DB::table('customer_marketing_preferences')->updateOrInsert(
            ['customer_id' => $customer->id], // unique key
            [
                'accepts_marketing' => rand(0, 1),
                'accepts_sms' => rand(0, 1),
                'accepts_phone_calls' => rand(0, 1),
                'accepts_push_notifications' => rand(0, 1),
                'channel_preferences' => json_encode([
                    'email' => rand(0, 1),
                    'sms' => rand(0, 1),
                    'push' => rand(0, 1),
                ]),
                'frequency_preferences' => json_encode([
                    'daily' => false,
                    'weekly' => rand(0, 1),
                    'monthly' => rand(0, 1),
                ]),
                'content_preferences' => json_encode([
                    'promotions' => rand(0, 1),
                    'new_products' => rand(0, 1),
                    'order_updates' => true,
                ]),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    /**
     * Create customer statistics
     */
    private function createCustomerStatistics($customer): void
    {
        DB::table('customer_statistics')->updateOrInsert(
            ['customer_id' => $customer->id],
            [
                'first_order_at' => null,
                'last_order_at' => null,
                'total_orders' => 0,
                'total_spent' => 0,
                'average_order_value' => 0,
                'customer_lifetime_value' => 0,
                'lifetime_days' => null,
                'days_since_last_order' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    /**
     * Create customer preferences
     */
    private function createCustomerPreferences($customer): void
    {
        $preferences = [
            [
                'category' => 'communication',
                'preference_key' => 'language',
                'preference_value' => json_encode('en'),
            ],
            [
                'category' => 'communication',
                'preference_key' => 'timezone',
                'preference_value' => json_encode('Africa/Nairobi'),
            ],
            [
                'category' => 'notifications',
                'preference_key' => 'email_frequency',
                'preference_value' => json_encode('weekly'),
            ],
            [
                'category' => 'privacy',
                'preference_key' => 'data_sharing',
                'preference_value' => json_encode(rand(0, 1)),
            ],
        ];

        foreach ($preferences as $pref) {
            DB::table('customer_preferences')->updateOrInsert(
                [
                    'customer_id' => $customer->id,
                    'category' => $pref['category'],
                    'preference_key' => $pref['preference_key'],
                ],
                [
                    'preference_value' => $pref['preference_value'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

        }
    }
}
