<?php

namespace Database\Seeders;

use App\Models\NewsLetter;
use App\Models\User;
use Illuminate\Database\Seeder;

class NewsletterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if it's SaaS mode
        $isSaas = config('app.is_saas', false);
        
        if ($isSaas) {
            // For SaaS mode - create newsletters for super admin
            $superAdmin = User::where('type', 'superadmin')->first();
            
            if (!$superAdmin) {
                $this->command->warn('No super admin user found. Please run UserSeeder first.');
                return;
            }
            
            $this->createNewslettersForUser($superAdmin);
        } else {
            // For non-SaaS mode - create newsletters for companies
            $companies = User::where('type', 'company')->get();

            if ($companies->isEmpty()) {
                $this->command->warn('No company users found. Please run DefaultCompanySeeder first.');
                return;
            }

            foreach ($companies as $company) {
                $this->createNewslettersForUser($company);
            }
        }

        $this->command->info('NewsletterSeeder completed successfully!');
    }

    private function createNewslettersForUser($user)
    {
        // Sample newsletter subscription data
        $newsletters = [
            ['email' => 'john.doe@example.com', 'status' => 'subscribed'],
            ['email' => 'jane.smith@company.com', 'status' => 'subscribed'],
            ['email' => 'michael.johnson@business.org', 'status' => 'subscribed'],
            ['email' => 'sarah.williams@startup.io', 'status' => 'subscribed'],
            ['email' => 'david.brown@enterprise.com', 'status' => 'subscribed'],
            ['email' => 'lisa.davis@tech.com', 'status' => 'subscribed'],
            ['email' => 'robert.miller@consulting.net', 'status' => 'subscribed'],
            ['email' => 'jennifer.wilson@nonprofit.org', 'status' => 'subscribed'],
            ['email' => 'mark.taylor@agency.com', 'status' => 'subscribed'],
            ['email' => 'amanda.anderson@corporation.net', 'status' => 'subscribed'],
            ['email' => 'chris.martinez@solutions.com', 'status' => 'subscribed'],
            ['email' => 'emily.garcia@innovations.net', 'status' => 'subscribed'],
            ['email' => 'daniel.rodriguez@systems.org', 'status' => 'subscribed'],
            ['email' => 'michelle.lopez@services.com', 'status' => 'subscribed'],
            ['email' => 'kevin.hernandez@digital.io', 'status' => 'subscribed'],
            ['email' => 'rachel.gonzalez@creative.net', 'status' => 'subscribed'],
            ['email' => 'brian.perez@marketing.com', 'status' => 'subscribed'],
            ['email' => 'stephanie.sanchez@design.org', 'status' => 'subscribed'],
            ['email' => 'jason.ramirez@development.com', 'status' => 'subscribed'],
            ['email' => 'nicole.torres@management.net', 'status' => 'subscribed']
        ];

        // Create all newsletters for each user
        foreach ($newsletters as $newsletter) {
            try {
                NewsLetter::updateOrCreate(
                    [
                        'email' => $newsletter['email']
                    ],
                    [
                        'status' => $newsletter['status'],
                    ]
                );
            } catch (\Exception $e) {
                $this->command->error('Failed to create/update newsletter: ' . $newsletter['email'] . ' for user: ' . $user->name);
                continue;
            }
        }
    }
}