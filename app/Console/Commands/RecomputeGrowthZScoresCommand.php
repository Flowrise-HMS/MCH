<?php

namespace Modules\MCH\Console\Commands;

use Illuminate\Console\Command;
use Modules\MCH\Classes\Services\GrowthZScoreService;
use Modules\MCH\Models\GrowthMeasurement;

class RecomputeGrowthZScoresCommand extends Command
{
    protected $signature = 'mch:recompute-zscores {--patient= : Only this patient id}';

    protected $description = 'Recompute WHO Z-scores (WAZ/HAZ/HCZ/ACZ and WHZ) for existing growth measurements.';

    public function handle(GrowthZScoreService $service): int
    {
        $count = 0;

        GrowthMeasurement::query()
            ->withoutGlobalScope('branch')
            ->when($this->option('patient'), fn ($query, $id) => $query->where('patient_id', $id))
            ->orderBy('id')
            ->chunkById(500, function ($measurements) use ($service, &$count): void {
                foreach ($measurements as $measurement) {
                    $service->apply($measurement);
                    $measurement->saveQuietly();
                    $service->syncWeightForLength($measurement);
                    $count++;
                }
            });

        $this->info("Recomputed {$count} measurements.");

        return self::SUCCESS;
    }
}
