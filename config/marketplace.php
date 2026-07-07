<?php

/*
|--------------------------------------------------------------------------
| Marketplace Verticals
|--------------------------------------------------------------------------
|
| Single source of truth for the "All Marketplace" feature. Each vertical is
| a browsing mode that maps to one or more top-level category slugs and
| declares the filter widgets shown for it. This same config drives the
| backend query (MarketplaceController + FrontendProductService) AND the
| frontend filter rendering (shipped as the `filterSchema` prop), so filters
| stay DRY. Add a new vertical by adding one entry here.
|
| filter types:
|   'select' — dropdown; options are derived from the data (distinct values of
|              the matching `attributes.<key>`), unless `options` is provided.
|   'range'  — two number inputs; `min`/`max` are the query-string param names.
|              key `price` filters on the variant selling_price; any other key
|              filters numerically on `attributes.<key>`.
|
*/

return [

    'default' => 'ecommerce',

    'verticals' => [

        'ecommerce' => [
            'key'            => 'ecommerce',
            'label'          => 'All Products',
            'tagline'        => 'Everyday shopping — electronics, fashion, home & more',
            'type'           => 'ecommerce',
            'icon'           => 'ShoppingBag',
            'category_slugs' => [],
            'attributes'     => [],
            'filters'        => [],
        ],

        'cars' => [
            'key'            => 'cars',
            'label'          => 'Cars & Motors',
            'tagline'        => 'Buy & sell cars, bikes and vehicles',
            'type'           => 'automotive',
            'icon'           => 'Car',
            'category_slugs' => ['cars-motors'],
            'attributes'     => ['make', 'model', 'year', 'mileage', 'fuel', 'transmission', 'body_type', 'drive_type', 'steering', 'color', 'engine_cc', 'condition', 'location'],
            'filters'        => [
                ['key' => 'stock_id',     'label' => 'Stock ID',     'type' => 'text'],
                ['key' => 'availability', 'label' => 'Availability', 'type' => 'select', 'options' => [
                    ['value' => 'available', 'label' => 'Available'],
                    ['value' => 'reserved',  'label' => 'Reserved'],
                ]],
                ['key' => 'make',         'label' => 'Make',         'type' => 'select'],
                ['key' => 'model',        'label' => 'Model',        'type' => 'select'],
                ['key' => 'body_type',    'label' => 'Body type',    'type' => 'select'],
                ['key' => 'fuel',         'label' => 'Fuel',         'type' => 'select'],
                ['key' => 'transmission', 'label' => 'Transmission', 'type' => 'select'],
                ['key' => 'drive_type',   'label' => 'Drive',        'type' => 'select'],
                ['key' => 'steering',     'label' => 'Steering',     'type' => 'select'],
                ['key' => 'color',        'label' => 'Colour',       'type' => 'select'],
                ['key' => 'condition',    'label' => 'Condition',    'type' => 'select'],
                ['key' => 'year',         'label' => 'Year',         'type' => 'range', 'min' => 'year_min',      'max' => 'year_max', 'ui' => 'year'],
                ['key' => 'mileage',      'label' => 'Mileage (km)', 'type' => 'range', 'min' => 'mileage_min',   'max' => 'mileage_max'],
                ['key' => 'engine_cc',    'label' => 'Engine (cc)',  'type' => 'range', 'min' => 'engine_cc_min', 'max' => 'engine_cc_max'],
                ['key' => 'price',        'label' => 'Price (KSh)',  'type' => 'range', 'min' => 'min_price',     'max' => 'max_price'],
            ],
            'quick_tags' => [
                ['label' => 'Available only', 'params' => ['availability' => 'available']],
                ['label' => 'Hybrid',         'params' => ['fuel' => 'Hybrid']],
                ['label' => 'Automatic',      'params' => ['transmission' => 'Automatic']],
                ['label' => '4WD / AWD',      'params' => ['drive_type' => 'AWD']],
                ['label' => 'Low mileage',    'params' => ['mileage_max' => 50000]],
            ],
            // Fields shown on the "post a car" form (admin + seller).
            'form_fields' => [
                ['key' => 'make',         'label' => 'Make',         'type' => 'make',   'required' => true],
                ['key' => 'model',        'label' => 'Model',        'type' => 'model',  'required' => true],
                ['key' => 'year',         'label' => 'Year',         'type' => 'number', 'required' => true],
                ['key' => 'mileage',      'label' => 'Mileage (km)', 'type' => 'number'],
                ['key' => 'engine_cc',    'label' => 'Engine (cc)',  'type' => 'number'],
                ['key' => 'body_type',    'label' => 'Body type',    'type' => 'select', 'options' => ['Sedan', 'Hatchback', 'SUV', 'Pickup', 'Coupe', 'Station Wagon', 'Van', 'Minivan', 'Truck', 'Bus', 'Motorbike']],
                ['key' => 'fuel',         'label' => 'Fuel',         'type' => 'select', 'options' => ['Petrol', 'Diesel', 'Hybrid', 'Electric', 'LPG']],
                ['key' => 'transmission', 'label' => 'Transmission', 'type' => 'select', 'options' => ['Automatic', 'Manual', 'CVT']],
                ['key' => 'drive_type',   'label' => 'Drive',        'type' => 'select', 'options' => ['2WD', '4WD', 'AWD']],
                ['key' => 'steering',     'label' => 'Steering',     'type' => 'select', 'options' => ['Right', 'Left']],
                ['key' => 'color',        'label' => 'Colour',       'type' => 'text'],
                ['key' => 'condition',    'label' => 'Condition',    'type' => 'select', 'options' => ['New', 'Used', 'Certified']],
                ['key' => 'location',     'label' => 'Location',     'type' => 'text'],
                // Buyers contact the seller directly on this number (call button on the card).
                ['key' => 'phone',        'label' => 'Contact phone', 'type' => 'text', 'required' => true],

                // ---- Advanced / optional details (shown behind "View more") ----
                ['key' => 'wheel_type',   'label' => 'Wheel type',    'type' => 'select', 'advanced' => true, 'options' => ['Alloy', 'Steel', 'Chrome', 'Sport / Racing']],
                ['key' => 'wheel_size',   'label' => 'Wheel size',    'type' => 'select', 'advanced' => true, 'options' => ['13"', '14"', '15"', '16"', '17"', '18"', '19"', '20"', '21"', '22"']],
                ['key' => 'doors',        'label' => 'Doors',         'type' => 'select', 'advanced' => true, 'options' => ['2', '3', '4', '5']],
                ['key' => 'seats',        'label' => 'Seats',         'type' => 'select', 'advanced' => true, 'options' => ['2', '4', '5', '7', '8', '9+']],
                ['key' => 'interior',     'label' => 'Interior',      'type' => 'select', 'advanced' => true, 'options' => ['Leather', 'Fabric', 'Vinyl', 'Suede', 'Part-leather']],
                ['key' => 'interior_color', 'label' => 'Interior colour', 'type' => 'text', 'advanced' => true],
                ['key' => 'horsepower',   'label' => 'Horsepower (hp)', 'type' => 'number', 'advanced' => true],
                ['key' => 'torque',       'label' => 'Torque (Nm)',   'type' => 'number', 'advanced' => true],
                ['key' => 'registration', 'label' => 'Registration / plate', 'type' => 'text', 'advanced' => true],
                ['key' => 'prev_owners',  'label' => 'Previous owners', 'type' => 'number', 'advanced' => true],
                // Multi-select feature checklist (stored as an array in attributes.features).
                ['key' => 'features',     'label' => 'Features & extras', 'type' => 'checklist', 'advanced' => true, 'options' => [
                    'Sunroof', 'Moonroof', 'Leather seats', 'Heated seats', 'Power seats', 'Navigation / GPS',
                    'Reverse camera', '360° camera', 'Parking sensors', 'Cruise control', 'Adaptive cruise',
                    'Bluetooth', 'Apple CarPlay', 'Android Auto', 'Air conditioning', 'Climate control',
                    'Power windows', 'Power steering', 'Keyless entry', 'Push-button start', 'Alloy wheels',
                    'Fog lights', 'LED headlights', 'Xenon headlights', 'ABS', 'Airbags', 'Traction control',
                    'Lane assist', 'Blind spot monitor', 'Tow bar', 'Roof rack', 'Spare tyre',
                ]],
            ],
            'sorts' => [
                ['key' => 'newest',     'label' => 'Newest first'],
                ['key' => 'price_asc',  'label' => 'Price: low to high'],
                ['key' => 'price_desc', 'label' => 'Price: high to low'],
                ['key' => 'year_desc',  'label' => 'Year: newest'],
                ['key' => 'mileage_asc','label' => 'Mileage: lowest'],
            ],
        ],

        'construction' => [
            'key'            => 'construction',
            'label'          => 'Building & Construction',
            'tagline'        => 'Materials, tools and equipment for building',
            'type'           => 'construction',
            'icon'           => 'HardHat',
            'category_slugs' => ['building-construction'],
            'attributes'     => ['material', 'type', 'brand', 'grade', 'size', 'unit', 'coverage', 'coverage_unit', 'certification', 'min_order', 'condition', 'location'],
            'filters'        => [
                ['key' => 'stock_id',     'label' => 'SKU',          'type' => 'text'],
                ['key' => 'availability', 'label' => 'Availability', 'type' => 'select', 'options' => [
                    ['value' => 'in_stock', 'label' => 'In stock'],
                ]],
                ['key' => 'material', 'label' => 'Material',   'type' => 'select'],
                ['key' => 'type',     'label' => 'Type',       'type' => 'select'],
                ['key' => 'brand',    'label' => 'Brand',      'type' => 'select'],
                ['key' => 'grade',    'label' => 'Grade',      'type' => 'select'],
                ['key' => 'unit',     'label' => 'Sold per',   'type' => 'select'],
                ['key' => 'price',    'label' => 'Price (KSh)','type' => 'range', 'min' => 'min_price', 'max' => 'max_price'],
            ],
            'quick_tags' => [
                ['label' => 'In stock', 'params' => ['availability' => 'in_stock']],
                ['label' => 'Cement',   'params' => ['material' => 'Cement']],
                ['label' => 'Steel',    'params' => ['material' => 'Steel']],
                ['label' => 'Roofing',  'params' => ['type' => 'Roofing']],
            ],
            'sorts' => [
                ['key' => 'newest',     'label' => 'Newest first'],
                ['key' => 'price_asc',  'label' => 'Price: low to high'],
                ['key' => 'price_desc', 'label' => 'Price: high to low'],
            ],
            'form_fields' => [
                ['key' => 'material',      'label' => 'Material',            'type' => 'text', 'required' => true],
                ['key' => 'type',          'label' => 'Type',                'type' => 'text'],
                ['key' => 'brand',         'label' => 'Brand',               'type' => 'text'],
                ['key' => 'grade',         'label' => 'Grade / Class',       'type' => 'text'],
                ['key' => 'size',          'label' => 'Size / Dimensions',   'type' => 'text'],
                ['key' => 'unit',          'label' => 'Sold per',            'type' => 'select', 'options' => ['piece', 'bag', 'tonne', 'metre', 'sq metre', 'litre', 'set', 'roll', 'sheet']],
                ['key' => 'coverage',      'label' => 'Coverage per unit',   'type' => 'number'],
                ['key' => 'coverage_unit', 'label' => 'Coverage unit',       'type' => 'select', 'options' => ['sq metre', 'metre', 'litre', 'kg']],
                ['key' => 'certification', 'label' => 'Certification',        'type' => 'text'],
                ['key' => 'min_order',     'label' => 'Min order qty',       'type' => 'number'],
                ['key' => 'condition',     'label' => 'Condition',           'type' => 'select', 'options' => ['New', 'Used']],
                ['key' => 'location',      'label' => 'Location',            'type' => 'text'],
            ],
        ],

    ],
];
