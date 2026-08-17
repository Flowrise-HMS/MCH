<?php

namespace Modules\MCH\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Core\Models\Branch;
use Modules\MCH\Enums\MchRecordStatus;
use Modules\MCH\Models\MchRecord;

class MchBookIssuanceService
{
    /**
     * Uniqueness of active books is enforced here via transaction + owner-row lock
     * (without global scopes so BelongsToBranch cannot skip the lock). Serials are
     * allocated under a branch-row lock because the DB generated-column unique
     * index is unavailable on MariaDB (error 1901).
     */
    public function issue(
        Model $owner,
        Branch $branch,
        string $unit,
        array $consent = [],
        ?User $issuedBy = null,
    ): MchRecord {
        return DB::transaction(function () use ($owner, $branch, $unit, $consent, $issuedBy): MchRecord {
            $owner->newQuery()
                ->withoutGlobalScopes()
                ->whereKey($owner->getKey())
                ->lockForUpdate()
                ->first();

            if ($this->activeBookExists($owner, $branch)) {
                throw new \RuntimeException('An active MCH book already exists for this owner in this branch.');
            }

            Branch::query()->whereKey($branch->id)->lockForUpdate()->first();

            return MchRecord::create([
                'owner_type' => get_class($owner),
                'owner_id' => $owner->getKey(),
                'branch_id' => $branch->id,
                'serial_number' => $this->nextSerialNumber($branch, $unit),
                'unit' => $unit,
                'issue_date' => now()->toDateString(),
                'status' => MchRecordStatus::ACTIVE,
                'data_consented' => $consent['data_consented'] ?? false,
                'consented_at' => ($consent['data_consented'] ?? false) ? now() : null,
                'consented_by' => ($consent['data_consented'] ?? false) ? ($consent['consented_by'] ?? $issuedBy?->id) : null,
            ]);
        });
    }

    public function replace(MchRecord $current, ?User $issuedBy = null): MchRecord
    {
        return DB::transaction(function () use ($current, $issuedBy): MchRecord {
            if ($current->status !== MchRecordStatus::ACTIVE) {
                throw new \RuntimeException('Only an active book can be replaced.');
            }

            $current->forceFill(['status' => MchRecordStatus::REPLACED])->save();

            $replacement = $this->issue($current->owner, $current->branch, $current->unit, issuedBy: $issuedBy);

            $current->forceFill(['replaced_by' => $replacement->id])->save();

            return $replacement;
        });
    }

    public function activeBookExists(Model $owner, Branch $branch): bool
    {
        return MchRecord::query()
            ->where('owner_type', get_class($owner))
            ->where('owner_id', $owner->getKey())
            ->where('branch_id', $branch->id)
            ->where('status', MchRecordStatus::ACTIVE)
            ->exists();
    }

    private function nextSerialNumber(Branch $branch, string $unit): string
    {
        $latestSerial = MchRecord::query()
            ->withoutGlobalScopes()
            ->where('branch_id', $branch->id)
            ->where('unit', $unit)
            ->max('serial_number');

        $n = 1;
        if (is_string($latestSerial)) {
            $n = ((int) substr($latestSerial, strrpos($latestSerial, '-') + 1)) + 1;
        }

        return sprintf('%s-%04d', strtoupper($unit), $n);
    }
}
