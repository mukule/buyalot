<?php

namespace Database\Seeders;

use App\Models\BusinessType;
use App\Models\BusinessTypeRequiredDocument;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            PermissionSeeder::class,
            // Optionally create more seeders here, e.g.
            // TestUserSeeder::class,
        ]);

        // Only seed business types if none exist
        if (BusinessType::count() === 0) {
            $this->seedBusinessTypes();
        }
    }

    protected function seedBusinessTypes(): void
    {
        $businessTypes = [
            [
                'name' => 'Retailer',
                'slug' => 'retailer',
                'description' => 'Sells products directly to consumers',
                'documents' => [
                    ['document_name' => 'Business Registration', 'is_required' => true],
                    ['document_name' => 'Tax Compliance Certificate', 'is_required' => true],
                    ['document_name' => 'Proof of Physical Address', 'is_required' => true]
                ]
            ],
            [
                'name' => 'Wholesaler',
                'slug' => 'wholesaler',
                'description' => 'Sells products in bulk to retailers',
                'documents' => [
                    ['document_name' => 'Business Registration', 'is_required' => true],
                    ['document_name' => 'Tax Compliance Certificate', 'is_required' => true],
                    ['document_name' => 'Wholesale License', 'is_required' => true],
                    ['document_name' => 'Supplier Agreements', 'is_required' => false]
                ]
            ],
            [
                'name' => 'Manufacturer',
                'slug' => 'manufacturer',
                'description' => 'Produces goods from raw materials',
                'documents' => [
                    ['document_name' => 'Business Registration', 'is_required' => true],
                    ['document_name' => 'Manufacturing License', 'is_required' => true],
                    ['document_name' => 'Product Certifications', 'is_required' => false],
                    ['document_name' => 'Factory Inspection Report', 'is_required' => true]
                ]
            ],
            [
                'name' => 'Distributor',
                'slug' => 'distributor',
                'description' => 'Distributes products to retailers',
                'documents' => [
                    ['document_name' => 'Business Registration', 'is_required' => true],
                    ['document_name' => 'Distribution Agreements', 'is_required' => true],
                    ['document_name' => 'Warehouse Certifications', 'is_required' => false]
                ]
            ],
            [
                'name' => 'Importer',
                'slug' => 'importer',
                'description' => 'Imports goods from other countries',
                'documents' => [
                    ['document_name' => 'Business Registration', 'is_required' => true],
                    ['document_name' => 'Import License', 'is_required' => true],
                    ['document_name' => 'Customs Documentation', 'is_required' => true],
                    ['document_name' => 'Product Origin Certificates', 'is_required' => true]
                ]
            ]
        ];

        foreach ($businessTypes as $typeData) {
            // Create the business type
            $businessType = BusinessType::create([
                'name' => $typeData['name'],
                'slug' => $typeData['slug'],
                'description' => $typeData['description']
            ]);

            // Create associated documents
            foreach ($typeData['documents'] as $document) {
                BusinessTypeRequiredDocument::create([
                    'business_type_id' => $businessType->id,
                    'document_name' => $document['document_name'],
                    'is_required' => $document['is_required'],
                    'description' => $this->getDocumentDescription($document['document_name'])
                ]);
            }
        }
    }

    protected function getDocumentDescription(string $documentName): string
    {
        $descriptions = [
            'Business Registration' => 'Official certificate of business registration from government authorities',
            'Tax Compliance Certificate' => 'Proof of tax registration and compliance with local tax laws',
            'Proof of Physical Address' => 'Utility bill or lease agreement showing business physical location',
            'Wholesale License' => 'License permitting wholesale operations',
            'Manufacturing License' => 'Government-issued permit for manufacturing activities',
            'Import License' => 'Authorization to import goods into the country',
            // Add more descriptions as needed
        ];

        return $descriptions[$documentName] ?? 'Required document for verification purposes';
    }
}