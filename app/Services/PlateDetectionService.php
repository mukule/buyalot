<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * License-plate detection via an ANPR model (Plate Recognizer by default).
 *
 * Given a car photo, returns candidate plate bounding boxes as fractions of the
 * image (0..1) so the client can pre-draw the blur region regardless of the
 * displayed size. Used by the "Auto-detect plate" action in the blur editor.
 *
 * If no API token is configured the feature is simply disabled and the editor
 * falls back to manual blur.
 */
class PlateDetectionService
{
    public function enabled(): bool
    {
        return ! empty(config('services.plate_recognizer.token'));
    }

    /**
     * @return array<int, array{x: float, y: float, w: float, h: float, score: float}>
     */
    public function detect(UploadedFile $image): array
    {
        if (! $this->enabled()) {
            return [];
        }

        $size = @getimagesize($image->getRealPath());
        $width = $size[0] ?? 0;
        $height = $size[1] ?? 0;
        if ($width < 1 || $height < 1) {
            return [];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Token ' . config('services.plate_recognizer.token'),
            ])
                ->timeout(20)
                ->attach('upload', file_get_contents($image->getRealPath()), $image->getClientOriginalName() ?: 'car.jpg')
                ->post(config('services.plate_recognizer.url'));

            if (! $response->successful()) {
                Log::warning('Plate detection API error', ['status' => $response->status()]);
                return [];
            }

            $boxes = [];
            foreach ($response->json('results', []) as $result) {
                $box = $result['box'] ?? null;
                if (! $box) {
                    continue;
                }
                $x = max(0, ($box['xmin'] ?? 0) / $width);
                $y = max(0, ($box['ymin'] ?? 0) / $height);
                $w = min(1, (($box['xmax'] ?? 0) - ($box['xmin'] ?? 0)) / $width);
                $h = min(1, (($box['ymax'] ?? 0) - ($box['ymin'] ?? 0)) / $height);

                if ($w <= 0 || $h <= 0) {
                    continue;
                }

                $boxes[] = [
                    'x'     => round($x, 5),
                    'y'     => round($y, 5),
                    'w'     => round($w, 5),
                    'h'     => round($h, 5),
                    'score' => (float) ($result['score'] ?? 0),
                ];
            }

            return $boxes;
        } catch (\Throwable $e) {
            Log::warning('Plate detection failed', ['error' => $e->getMessage()]);
            return [];
        }
    }
}
