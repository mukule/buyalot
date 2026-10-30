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
                ['key' => 'body_type',    'label' => 'Body type',    'type' => 'select', 'for' => 'vehicle'],
                ['key' => 'fuel',         'label' => 'Fuel',         'type' => 'select', 'for' => 'vehicle'],
                ['key' => 'transmission', 'label' => 'Transmission', 'type' => 'select', 'for' => 'vehicle'],
                ['key' => 'drive_type',   'label' => 'Drive',        'type' => 'select', 'for' => 'vehicle'],
                ['key' => 'steering',     'label' => 'Steering',     'type' => 'select', 'for' => 'vehicle'],
                ['key' => 'color',        'label' => 'Colour',       'type' => 'select', 'for' => 'vehicle'],
                ['key' => 'condition',    'label' => 'Condition',    'type' => 'select'],
                ['key' => 'year',         'label' => 'Year',         'type' => 'range', 'min' => 'year_min',      'max' => 'year_max', 'ui' => 'year', 'for' => 'vehicle'],
                ['key' => 'mileage',      'label' => 'Mileage (km)', 'type' => 'range', 'min' => 'mileage_min',   'max' => 'mileage_max', 'for' => 'vehicle'],
                ['key' => 'engine_cc',    'label' => 'Engine (cc)',  'type' => 'range', 'min' => 'engine_cc_min', 'max' => 'engine_cc_max', 'for' => 'vehicle'],
                ['key' => 'part_group',   'label' => 'Part of the car', 'type' => 'select', 'for' => 'spare_part'],
                ['key' => 'part_type',    'label' => 'Part',            'type' => 'select', 'for' => 'spare_part'],
                ['key' => 'part_origin',  'label' => 'Genuine / aftermarket', 'type' => 'select', 'for' => 'spare_part'],
                ['key' => 'part_number',  'label' => 'Part number',     'type' => 'text',   'for' => 'spare_part'],
                ['key' => 'price',        'label' => 'Price (KSh)',  'type' => 'range', 'min' => 'min_price',     'max' => 'max_price'],
            ],
            'quick_tags' => [
                ['label' => 'Available only', 'params' => ['availability' => 'available'], 'for' => 'vehicle'],
                ['label' => 'Hybrid',         'params' => ['fuel' => 'Hybrid'], 'for' => 'vehicle'],
                ['label' => 'Automatic',      'params' => ['transmission' => 'Automatic'], 'for' => 'vehicle'],
                ['label' => '4WD / AWD',      'params' => ['drive_type' => 'AWD'], 'for' => 'vehicle'],
                ['label' => 'Low mileage',    'params' => ['mileage_max' => 50000], 'for' => 'vehicle'],
            ],
            // A Cars & Motors listing is either a whole vehicle or a spare part.
            // Listings saved before spare parts existed have no listing_type and count as vehicles.
            'listing_types' => [
                'vehicle'    => ['label' => 'Vehicles',    'icon' => 'Car'],
                'spare_part' => ['label' => 'Spare parts', 'icon' => 'Wrench'],
            ],
            'default_listing_type' => 'vehicle',
            // Spare parts are grouped by the part of the car they belong to (group => part types).
            'part_groups' => [
                'Engine'                     => ['Pistons & rings', 'Gaskets & seals', 'Timing belts & chains', 'Engine mounts', 'Cylinder heads', 'Turbochargers', 'Oil pumps', 'Valves & camshafts', 'Complete engines'],
                'Ignition & Fuel System'     => ['Spark plugs', 'Ignition coils', 'Fuel pumps', 'Fuel injectors', 'Throttle bodies', 'Fuel filters', 'Sensors'],
                'Transmission & Drivetrain'  => ['Clutch kits', 'Gearboxes', 'CV joints & driveshafts', 'Differentials', 'Flywheels', 'Transmission mounts'],
                'Brakes'                     => ['Brake pads', 'Brake discs / rotors', 'Brake shoes & drums', 'Calipers', 'Master cylinders', 'Brake hoses & lines', 'ABS sensors'],
                'Suspension & Steering'      => ['Shock absorbers', 'Coil springs', 'Control arms', 'Ball joints', 'Bushings', 'Tie rod ends', 'Steering racks', 'Power steering pumps', 'Stabilizer links'],
                'Cooling & Air Conditioning' => ['Radiators', 'Water pumps', 'Thermostats', 'Cooling fans', 'Hoses', 'AC compressors', 'Condensers'],
                'Electrical & Lighting'      => ['Batteries', 'Alternators', 'Starter motors', 'Headlights', 'Tail lights', 'Fog lights', 'Bulbs', 'Switches & relays', 'Wiring & fuses', 'ECUs'],
                'Exhaust'                    => ['Silencers / mufflers', 'Catalytic converters', 'Exhaust pipes', 'Manifolds', 'O2 sensors'],
                'Body & Exterior'            => ['Bumpers', 'Bonnets', 'Doors', 'Fenders', 'Side mirrors', 'Grilles', 'Windscreens & glass', 'Wiper blades'],
                'Interior'                   => ['Seats & covers', 'Dashboards', 'Steering wheels', 'Floor mats', 'Door panels', 'Radios & infotainment'],
                'Wheels & Tyres'             => ['Tyres', 'Rims', 'Wheel bearings', 'Hub caps', 'Wheel nuts'],
                'Filters & Service Parts'    => ['Oil filters', 'Air filters', 'Cabin filters', 'Engine oil', 'Coolant', 'Brake fluid', 'Service kits'],
                'Accessories'                => ['Car covers', 'Roof racks', 'Dash cams', 'Phone holders', 'Tools & jacks', 'Other accessories'],
            ],
            // Fields shown on the "post a car" form (admin + seller).
            'form_fields' => [
                // Vehicle or spare part — decides which of the fields below apply ('for').
                ['key' => 'listing_type', 'label' => 'Listing type', 'type' => 'listing_type', 'required' => true],

                // ---- Spare parts ----
                ['key' => 'part_group',   'label' => 'Part of the car',  'type' => 'part_group', 'required' => true, 'for' => 'spare_part'],
                ['key' => 'part_type',    'label' => 'Part',             'type' => 'part_type',  'for' => 'spare_part'],
                ['key' => 'part_number',  'label' => 'Part / OEM number', 'type' => 'text',      'for' => 'spare_part'],
                ['key' => 'part_origin',  'label' => 'Genuine or aftermarket', 'type' => 'select', 'options' => ['Genuine / OEM', 'Aftermarket', 'Ex-Japan / Ex-UK'], 'for' => 'spare_part'],
                ['key' => 'make',         'label' => 'Fits make',        'type' => 'make',   'for' => 'spare_part'],
                ['key' => 'model',        'label' => 'Fits model',       'type' => 'model',  'for' => 'spare_part'],
                ['key' => 'year_from',    'label' => 'Fits year from',   'type' => 'number', 'for' => 'spare_part'],
                ['key' => 'year_to',      'label' => 'Fits year to',     'type' => 'number', 'for' => 'spare_part'],
                ['key' => 'condition',    'label' => 'Condition',        'type' => 'select', 'options' => ['New', 'Used', 'Refurbished'], 'for' => 'spare_part'],
                ['key' => 'part_manufacturer', 'label' => 'Manufacturer (e.g. Bosch, Denso)', 'type' => 'text', 'advanced' => true, 'for' => 'spare_part'],
                ['key' => 'warranty',     'label' => 'Warranty',         'type' => 'text',   'advanced' => true, 'for' => 'spare_part'],

                // ---- Vehicles ----
                ['key' => 'make',         'label' => 'Make',         'type' => 'make',   'required' => true, 'for' => 'vehicle'],
                ['key' => 'model',        'label' => 'Model',        'type' => 'model',  'required' => true, 'for' => 'vehicle'],
                ['key' => 'year',         'label' => 'Year',         'type' => 'number', 'required' => true, 'for' => 'vehicle'],
                ['key' => 'mileage',      'label' => 'Mileage (km)', 'type' => 'number', 'for' => 'vehicle'],
                ['key' => 'engine_cc',    'label' => 'Engine (cc)',  'type' => 'number', 'for' => 'vehicle'],
                ['key' => 'body_type',    'label' => 'Body type',    'type' => 'select', 'options' => ['Sedan', 'Hatchback', 'SUV', 'Pickup', 'Coupe', 'Station Wagon', 'Van', 'Minivan', 'Truck', 'Bus', 'Motorbike'], 'for' => 'vehicle'],
                ['key' => 'fuel',         'label' => 'Fuel',         'type' => 'select', 'options' => ['Petrol', 'Diesel', 'Hybrid', 'Electric', 'LPG'], 'for' => 'vehicle'],
                ['key' => 'transmission', 'label' => 'Transmission', 'type' => 'select', 'options' => ['Automatic', 'Manual', 'CVT'], 'for' => 'vehicle'],
                ['key' => 'drive_type',   'label' => 'Drive',        'type' => 'select', 'options' => ['2WD', '4WD', 'AWD'], 'for' => 'vehicle'],
                ['key' => 'steering',     'label' => 'Steering',     'type' => 'select', 'options' => ['Right', 'Left'], 'for' => 'vehicle'],
                ['key' => 'color',        'label' => 'Colour',       'type' => 'text', 'for' => 'vehicle'],
                ['key' => 'condition',    'label' => 'Condition',    'type' => 'select', 'options' => ['New', 'Used', 'Certified'], 'for' => 'vehicle'],
                ['key' => 'location',     'label' => 'Location',     'type' => 'text'],
                // Buyers contact the seller directly on this number (call button on the card).
                ['key' => 'phone',        'label' => 'Contact phone', 'type' => 'text', 'required' => true],

                // ---- Advanced / optional details (shown behind "View more") ----
                ['key' => 'wheel_type',   'label' => 'Wheel type',    'type' => 'select', 'advanced' => true, 'options' => ['Alloy', 'Steel', 'Chrome', 'Sport / Racing'], 'for' => 'vehicle'],
                ['key' => 'wheel_size',   'label' => 'Wheel size',    'type' => 'select', 'advanced' => true, 'options' => ['13"', '14"', '15"', '16"', '17"', '18"', '19"', '20"', '21"', '22"'], 'for' => 'vehicle'],
                ['key' => 'doors',        'label' => 'Doors',         'type' => 'select', 'advanced' => true, 'options' => ['2', '3', '4', '5'], 'for' => 'vehicle'],
                ['key' => 'seats',        'label' => 'Seats',         'type' => 'select', 'advanced' => true, 'options' => ['2', '4', '5', '7', '8', '9+'], 'for' => 'vehicle'],
                ['key' => 'interior',     'label' => 'Interior',      'type' => 'select', 'advanced' => true, 'options' => ['Leather', 'Fabric', 'Vinyl', 'Suede', 'Part-leather'], 'for' => 'vehicle'],
                ['key' => 'interior_color', 'label' => 'Interior colour', 'type' => 'text', 'advanced' => true, 'for' => 'vehicle'],
                ['key' => 'horsepower',   'label' => 'Horsepower (hp)', 'type' => 'number', 'advanced' => true, 'for' => 'vehicle'],
                ['key' => 'torque',       'label' => 'Torque (Nm)',   'type' => 'number', 'advanced' => true, 'for' => 'vehicle'],
                ['key' => 'registration', 'label' => 'Registration / plate', 'type' => 'text', 'advanced' => true, 'for' => 'vehicle'],
                ['key' => 'prev_owners',  'label' => 'Previous owners', 'type' => 'number', 'advanced' => true, 'for' => 'vehicle'],
                // Multi-select feature checklist (stored as an array in attributes.features).
                ['key' => 'features',     'label' => 'Features & extras', 'type' => 'checklist', 'advanced' => true, 'for' => 'vehicle', 'options' => [
                    'Sunroof', 'Moonroof', 'Leather seats', 'Heated seats', 'Power seats', 'Navigation / GPS',
                    'Reverse camera', '360° camera', 'Parking sensors', 'Cruise control', 'Adaptive cruise',
                    'Bluetooth', 'Apple CarPlay', 'Android Auto', 'Air conditioning', 'Climate control',
                    'Power windows', 'Power steering', 'Keyless entry', 'Push-button start', 'Alloy wheels',
                    'Fog lights', 'LED headlights', 'Xenon headlights', 'ABS', 'Airbags', 'Traction control',
                    'Lane assist', 'Blind spot monitor', 'Tow bar', 'Roof rack', 'Spare tyre',
                ]],
            ],
            'sorts' => [
                ['key' => 'part_group', 'label' => 'Part of the car', 'for' => 'spare_part'],
                ['key' => 'newest',     'label' => 'Newest first'],
                ['key' => 'price_asc',  'label' => 'Price: low to high'],
                ['key' => 'price_desc', 'label' => 'Price: high to low'],
                ['key' => 'year_desc',  'label' => 'Year: newest', 'for' => 'vehicle'],
                ['key' => 'mileage_asc','label' => 'Mileage: lowest', 'for' => 'vehicle'],
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
