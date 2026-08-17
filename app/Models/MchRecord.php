<?php

namespace Modules\MCH\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Modules\Core\Models\BaseModel;
use Modules\MCH\Enums\MchRecordStatus;

class MchRecord extends BaseModel
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';

    protected $fillable = [
        'owner_type',
        'owner_id',
        'branch_id',
        'serial_number',
        'unit',
        'issue_date',
        'status',
        'replaced_by',
        'data_consented',
        'consented_at',
        'consented_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'status' => MchRecordStatus::class,
        'data_consented' => 'boolean',
        'consented_at' => 'datetime',
    ];

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    public function replacement(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaced_by');
    }

    public function consentingUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'consented_by');
    }
}
