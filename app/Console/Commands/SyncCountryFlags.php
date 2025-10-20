<?php

namespace App\Console\Commands;

use App\Models\Country;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class SyncCountryFlags extends Command
{
    protected $signature = 'countries:sync-flags {--codes=* : ISO2 codes to sync (e.g., KE US GB). If omitted, a default set is used} {--style=flat : FlagsAPI style (flat or shiny)} {--size=64 : FlagsAPI size (16, 24, 32, 48, 64) }';

    protected $description = 'Download country flags from FlagsAPI and store them locally, updating the countries table.';

    public function handle(): int
    {
        $codes = $this->option('codes');
        $style = in_array($this->option('style'), ['flat', 'shiny']) ? $this->option('style') : 'flat';
        $size = (int) $this->option('size');
        if (!in_array($size, [16, 24, 32, 48, 64])) {
            $size = 64;
        }

        // Default minimal set if none provided; extend as needed or feed via --codes
        if (empty($codes)) {
            $codes = [
                'KE','US','GB','CA','AU','DE','FR','IT','ES','ZA','NG','TZ','UG','RW','BI','ET','AE','IN','CN','JP','BR','MX'
            ];
        }

        $disk = Storage::disk('public');
        $folder = 'flags';
        if (!$disk->exists($folder)) {
            $disk->makeDirectory($folder);
        }

        $bar = $this->output->createProgressBar(count($codes));
        $bar->start();
        $errors = 0;

        foreach ($codes as $code) {
            $iso2 = strtoupper(trim($code));
            if (strlen($iso2) !== 2) {
                $errors++;
                $bar->advance();
                continue;
            }

            $url = sprintf('https://flagsapi.com/%s/%s/%d.png', $iso2, $style, $size);

            try {
                $response = Http::timeout(20)->retry(2, 500)->get($url);
                if (!$response->ok() || empty($response->body())) {
                    $this->warn("Failed to fetch flag for {$iso2}: HTTP " . $response->status());
                    $errors++;
                    $bar->advance();
                    continue;
                }

                $filename = strtolower($iso2) . '.png';
                $path = $folder . '/' . $filename;
                $disk->put($path, $response->body());

                Country::updateOrCreate(
                    ['iso2' => $iso2],
                    ['flag_path' => $path]
                );
            } catch (\Throwable $e) {
                $this->warn("Error syncing {$iso2}: " . $e->getMessage());
                $errors++;
            }

            // Be gentle to the API
            usleep(150000); // 150ms
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        if ($errors > 0) {
            $this->info('Completed with ' . $errors . ' errors.');
            return self::SUCCESS;
        }

        $this->info('All flags synced successfully.');
        return self::SUCCESS;
    }
}
