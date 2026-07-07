<?php

namespace Database\Seeders;

use App\Models\CarMake;
use App\Models\CarModel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds the canonical vehicle makes (brands) and their models for the
 * Cars & Motors marketplace. Idempotent — safe to re-run; new models added
 * here on subsequent runs are inserted without duplicating existing rows.
 *
 * Coverage is weighted toward makes common in the East African / Kenyan
 * used-import market (Japanese, German, Korean) plus mainstream global brands
 * and popular motorbike makes.
 */
class CarMakeSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->data() as $make => $models) {
            $carMake = CarMake::updateOrCreate(
                ['slug' => Str::slug($make)],
                ['name' => $make, 'active' => true],
            );

            foreach ($models as $model) {
                CarModel::updateOrCreate(
                    ['car_make_id' => $carMake->id, 'slug' => Str::slug($model)],
                    ['name' => $model, 'active' => true],
                );
            }
        }

        $this->command?->info('Seeded ' . CarMake::count() . ' makes and ' . CarModel::count() . ' models.');
    }

    /**
     * @return array<string, string[]>
     */
    protected function data(): array
    {
        return [
            'Toyota' => [
                'Corolla', 'Corolla Fielder', 'Corolla Axio', 'Camry', 'Vitz', 'Yaris', 'Passo', 'Aqua', 'Prius',
                'Premio', 'Allion', 'Mark X', 'Crown', 'Auris', 'Ractis', 'Belta', 'Probox', 'Succeed', 'Wish',
                'Voxy', 'Noah', 'Alphard', 'Vellfire', 'Sienta', 'Isis', 'Estima', 'RAV4', 'Harrier', 'Vanguard',
                'Rush', 'Fortuner', 'Land Cruiser', 'Land Cruiser Prado', 'Hilux', 'Hilux Surf', 'Hiace', 'Regius',
                'Townace', 'Liteace', 'Dyna', 'Coaster', 'Land Cruiser 70', 'C-HR', 'bZ4X', 'Raize', 'Passo Sette',
            ],
            'Nissan' => [
                'March', 'Note', 'Tiida', 'Sylphy', 'Bluebird', 'Sunny', 'Latio', 'Wingroad', 'AD Van', 'Serena',
                'X-Trail', 'Qashqai', 'Juke', 'Dualis', 'Murano', 'Patrol', 'Pathfinder', 'Navara', 'Terrano',
                'Skyline', 'GT-R', 'Fairlady Z', 'Teana', 'Cefiro', 'Cube', 'Leaf', 'Elgrand', 'Caravan', 'NV350',
                'Kicks', 'Fuga', 'Presage', 'Lafesta',
            ],
            'Mazda' => [
                'Demio', 'Mazda2', 'Mazda3', 'Axela', 'Mazda6', 'Atenza', 'CX-3', 'CX-30', 'CX-5', 'CX-8', 'CX-9',
                'Premacy', 'Biante', 'MPV', 'Bongo', 'BT-50', 'Familia', 'Verisa', 'Carol', 'MX-5', 'RX-8',
            ],
            'Honda' => [
                'Fit', 'Fit Shuttle', 'Jazz', 'Civic', 'Accord', 'Insight', 'Vezel', 'HR-V', 'CR-V', 'CR-Z',
                'Freed', 'Stepwgn', 'Odyssey', 'Stream', 'Airwave', 'Grace', 'Shuttle', 'Mobilio', 'Pilot',
                'City', 'Legend', 'N-Box', 'N-WGN',
            ],
            'Subaru' => [
                'Impreza', 'WRX', 'WRX STI', 'Legacy', 'Legacy B4', 'Outback', 'Forester', 'XV', 'Crosstrek',
                'Levorg', 'Exiga', 'Trezia', 'BRZ', 'Justy', 'Sambar',
            ],
            'Mitsubishi' => [
                'Lancer', 'Lancer Evolution', 'Mirage', 'Colt', 'Galant', 'Outlander', 'ASX', 'RVR', 'Eclipse Cross',
                'Pajero', 'Pajero Sport', 'Montero', 'Shogun', 'Delica', 'L200', 'Triton', 'Canter', 'Fuso',
                'Attrage', 'Xpander',
            ],
            'Suzuki' => [
                'Alto', 'Swift', 'Baleno', 'Celerio', 'Wagon R', 'Solio', 'Ignis', 'SX4', 'Vitara', 'Escudo',
                'Grand Vitara', 'Jimny', 'Ertiga', 'APV', 'Every', 'Carry', 'Spacia', 'Hustler',
            ],
            'Isuzu' => [
                'D-Max', 'MU-X', 'Trooper', 'Bighorn', 'Wizard', 'NPR', 'NQR', 'FRR', 'FVR', 'Forward', 'Elf', 'Giga',
            ],
            'Daihatsu' => [
                'Mira', 'Move', 'Tanto', 'Cast', 'Boon', 'Sirion', 'Terios', 'Hijet', 'Rocky', 'Wake', 'Copen',
            ],
            'Lexus' => [
                'IS', 'ES', 'GS', 'LS', 'CT', 'UX', 'NX', 'RX', 'GX', 'LX', 'RC', 'LC',
            ],
            'Volkswagen' => [
                'Polo', 'Golf', 'Golf GTI', 'Jetta', 'Passat', 'Vento', 'Beetle', 'Scirocco', 'Touran', 'Sharan',
                'Tiguan', 'Touareg', 'T-Cross', 'T-Roc', 'Amarok', 'Caddy', 'Transporter',
            ],
            'Mercedes-Benz' => [
                'A-Class', 'B-Class', 'C-Class', 'C200', 'C180', 'E-Class', 'E250', 'S-Class', 'CLA', 'CLS', 'CLK',
                'GLA', 'GLB', 'GLC', 'GLE', 'GLS', 'ML', 'GL', 'G-Class', 'G-Wagon', 'Vito', 'Sprinter', 'Actros',
            ],
            'BMW' => [
                '1 Series', '2 Series', '3 Series', '316i', '318i', '320i', '4 Series', '5 Series', '520i', '523i',
                '6 Series', '7 Series', 'X1', 'X2', 'X3', 'X4', 'X5', 'X6', 'X7', 'Z4', 'M3', 'M4', 'M5', 'i3', 'iX',
            ],
            'Audi' => [
                'A1', 'A3', 'A4', 'A5', 'A6', 'A7', 'A8', 'Q2', 'Q3', 'Q5', 'Q7', 'Q8', 'TT', 'R8', 'e-tron',
                'S3', 'S4', 'RS4', 'RS6',
            ],
            'Volvo' => [
                'S40', 'S60', 'S80', 'S90', 'V40', 'V60', 'V90', 'XC40', 'XC60', 'XC90',
            ],
            'Land Rover' => [
                'Defender', 'Discovery', 'Discovery Sport', 'Freelander', 'Range Rover', 'Range Rover Sport',
                'Range Rover Evoque', 'Range Rover Velar',
            ],
            'Jaguar' => [
                'XE', 'XF', 'XJ', 'F-Pace', 'E-Pace', 'I-Pace', 'F-Type',
            ],
            'Ford' => [
                'Fiesta', 'Focus', 'Fusion', 'Mondeo', 'Mustang', 'EcoSport', 'Kuga', 'Escape', 'Edge', 'Explorer',
                'Everest', 'Ranger', 'F-150', 'Transit',
            ],
            'Chevrolet' => [
                'Spark', 'Aveo', 'Sonic', 'Cruze', 'Malibu', 'Captiva', 'Trailblazer', 'Tahoe', 'Suburban',
                'Silverado', 'Colorado',
            ],
            'Hyundai' => [
                'i10', 'i20', 'i30', 'Accent', 'Elantra', 'Sonata', 'Getz', 'Creta', 'Kona', 'Tucson', 'Santa Fe',
                'Palisade', 'ix35', 'H1', 'Staria',
            ],
            'Kia' => [
                'Picanto', 'Rio', 'Cerato', 'Forte', 'Optima', 'K5', 'Stonic', 'Seltos', 'Sportage', 'Sorento',
                'Carnival', 'Soul', 'Stinger',
            ],
            'Peugeot' => [
                '107', '208', '301', '308', '3008', '407', '508', '2008', '5008', 'Partner', 'Boxer',
            ],
            'Renault' => [
                'Clio', 'Megane', 'Kwid', 'Duster', 'Captur', 'Koleos', 'Kadjar', 'Kangoo', 'Master',
            ],
            'Fiat' => [
                '500', 'Panda', 'Punto', 'Tipo', 'Doblo', 'Ducato',
            ],
            'Jeep' => [
                'Renegade', 'Compass', 'Cherokee', 'Grand Cherokee', 'Wrangler', 'Gladiator',
            ],
            'Tesla' => [
                'Model 3', 'Model S', 'Model X', 'Model Y',
            ],
            'Porsche' => [
                '911', 'Cayenne', 'Macan', 'Panamera', 'Cayman', 'Boxster', 'Taycan',
            ],
            'Mini' => [
                'Cooper', 'Cooper S', 'Countryman', 'Clubman', 'Paceman',
            ],
            'Datsun' => [
                'Go', 'Go+', 'Redi-Go',
            ],
            'Chery' => [
                'Tiggo 2', 'Tiggo 4', 'Tiggo 7', 'Tiggo 8', 'Arrizo 5',
            ],
            'Haval' => [
                'H2', 'H6', 'H9', 'Jolion', 'Dargo',
            ],
            'BYD' => [
                'Atto 3', 'Dolphin', 'Seal', 'Song', 'Tang', 'Han',
            ],
            'Mahindra' => [
                'Scorpio', 'XUV500', 'XUV700', 'Bolero', 'Thar', 'Pik Up',
            ],
            'Tata' => [
                'Indica', 'Indigo', 'Nexon', 'Harrier', 'Xenon', 'Super Ace',
            ],

            // ---- Motorbikes -------------------------------------------------
            'Bajaj' => [
                'Boxer', 'Boxer BM150', 'Pulsar', 'Discover', 'Platina', 'CT100', 'Avenger', 'Dominar', 'RE (Tuk Tuk)',
            ],
            'TVS' => [
                'Star HLX', 'Apache', 'Raider', 'Ntorq', 'Max 125', 'HLX 150', 'King (Tuk Tuk)',
            ],
            'Honda Motorcycle' => [
                'CB125', 'CG125', 'Ace 110', 'XR150', 'CRF', 'Wave',
            ],
            'Yamaha' => [
                'YBR125', 'Crux', 'Vega', 'Ray', 'FZ', 'YZF-R15', 'Crypton',
            ],
            'Suzuki Motorcycle' => [
                'GS150', 'GD110', 'Access 125', 'Gixxer',
            ],
            'Boxer' => [
                'BM100', 'BM150',
            ],
            'Captain' => [
                'Standard', 'Deluxe',
            ],
            'KTM' => [
                'Duke 125', 'Duke 200', 'Duke 390', 'RC 390', 'Adventure 390',
            ],
            'Kawasaki' => [
                'Ninja 250', 'Ninja 400', 'Z400', 'Z650', 'Versys',
            ],
            'Harley-Davidson' => [
                'Iron 883', 'Street 750', 'Fat Boy', 'Sportster',
            ],
        ];
    }
}
