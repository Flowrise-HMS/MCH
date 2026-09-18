<?php

namespace Modules\MCH\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\BaseModel;

class ImmunizationSchedule extends BaseModel
{
    use HasFactory, HasUuids;

    public const string TARGET_CHILD = 'child';

    public const string TARGET_MATERNAL = 'maternal';

    protected $keyType = 'string';

    protected $attributes = [
        'is_active' => true,
    ];

    protected $fillable = [
        'name',
        'description',
        'target_population',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function bootBelongsToBranch(): void
    {
        // Schedules are facility-wide catalogues — no branch_id.
    }

    /**
     * The active schedule for a population ('child' or 'maternal'), if one exists.
     */
    public static function activeFor(string $targetPopulation): ?self
    {
        return static::query()
            ->where('is_active', true)
            ->where('target_population', $targetPopulation)
            ->first();
    }

    /**
     * Maternal schedules space doses from the previous dose rather than from a date of birth.
     */
    public function isMaternal(): bool
    {
        return $this->target_population === self::TARGET_MATERNAL;
    }

    public function items(): HasMany
    {
        return $this->hasMany(ImmunizationScheduleItem::class);
    }
}
