<?php

namespace Modules\MCH\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Models\BaseModel;

class ImmunizationSchedule extends BaseModel
{
    use HasFactory, HasUuids;

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

    public function items(): HasMany
    {
        return $this->hasMany(ImmunizationScheduleItem::class);
    }
}
