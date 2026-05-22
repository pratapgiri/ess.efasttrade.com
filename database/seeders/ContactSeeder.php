<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Seeder;

class ContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if it's SaaS mode
        $isSaas = config('app.is_saas', false);
        
        if ($isSaas) {
            // For SaaS mode - create contacts for super admin
            $superAdmin = User::where('type', 'superadmin')->first();
            
            if (!$superAdmin) {
                $this->command->warn('No super admin user found. Please run UserSeeder first.');
                return;
            }
            
            $this->createContactsForUser($superAdmin);
        } else {
            // For non-SaaS mode - create contacts for companies
            $companies = User::where('type', 'company')->get();

            if ($companies->isEmpty()) {
                $this->command->warn('No company users found. Please run DefaultCompanySeeder first.');
                return;
            }

            foreach ($companies as $company) {
                $this->createContactsForUser($company);
            }
        }

        $this->command->info('ContactSeeder completed successfully!');
    }

    private function createContactsForUser($user)
    {
        // Sample contact data
        $contacts = [
            [
                'name' => 'John Smith',
                'email' => 'john.smith@example.com',
                'subject' => 'Inquiry about HR Management System',
                'message' => 'Hello, I am interested in learning more about your HR management system. Could you please provide more details about the features and pricing?',
                'status' => 'New'
            ],
            [
                'name' => 'Sarah Johnson',
                'email' => 'sarah.johnson@company.com',
                'subject' => 'Demo Request',
                'message' => 'Hi, we are looking for an HR solution for our growing company. Would it be possible to schedule a demo to see the system in action?',
                'status' => 'Contacted'
            ],
            [
                'name' => 'Michael Brown',
                'email' => 'michael.brown@business.org',
                'subject' => 'Integration Questions',
                'message' => 'We currently use several HR tools and would like to know about integration capabilities with your system. Can you help us understand the options?',
                'status' => 'Qualified'
            ],
            [
                'name' => 'Emily Davis',
                'email' => 'emily.davis@startup.io',
                'subject' => 'Pricing Information',
                'message' => 'Could you please send us detailed pricing information for your HR management system? We are a startup with about 25 employees.',
                'status' => 'New'
            ],
            [
                'name' => 'David Wilson',
                'email' => 'david.wilson@enterprise.com',
                'subject' => 'Enterprise Solution Inquiry',
                'message' => 'We are an enterprise with 500+ employees looking for a comprehensive HR solution. What enterprise features do you offer?',
                'status' => 'Contacted'
            ],
            [
                'name' => 'Lisa Anderson',
                'email' => 'lisa.anderson@tech.com',
                'subject' => 'Support Question',
                'message' => 'What kind of customer support do you provide? We need 24/7 support for our global operations.',
                'status' => 'Closed'
            ],
            [
                'name' => 'Robert Taylor',
                'email' => 'robert.taylor@consulting.net',
                'subject' => 'Partnership Opportunity',
                'message' => 'We are a consulting firm and would like to explore partnership opportunities. Could we schedule a call to discuss this?',
                'status' => 'Converted'
            ],
            [
                'name' => 'Jennifer Martinez',
                'email' => 'jennifer.martinez@nonprofit.org',
                'subject' => 'Non-profit Discount',
                'message' => 'Do you offer any special pricing for non-profit organizations? We are interested in your HR management solution.',
                'status' => 'New'
            ]
        ];

        // Create all contacts for each user
        foreach ($contacts as $contact) {

            try {
                Contact::updateOrCreate(
                    [
                        'email' => $contact['email'],
                        'created_by' => $user->id
                    ],
                    [
                        'name' => $contact['name'],
                        'subject' => $contact['subject'],
                        'message' => $contact['message'],
                        'status' => $contact['status'],
                    ]
                );
            } catch (\Exception $e) {
                $this->command->error('Failed to create/update contact: ' . $contact['name'] . ' for user: ' . $user->name);
                continue;
            }
        }
    }
}