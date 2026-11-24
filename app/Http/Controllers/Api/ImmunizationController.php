<?php

namespace Modules\MCH\Http\Controllers\Api;

use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\MCH\Classes\Services\ImmunizationRecordService;
use Modules\MCH\Enums\ImmunizationStatus;
use Modules\MCH\Models\ImmunizationRecord;

/**
 * @group Maternal and child health
 *
 * Immunization doses (scheduled from the EPI schedules), administered or declined.
 */
class ImmunizationController extends MchApiController
{
    protected string $defaultSort = 'due_date';

    public function __construct(protected ImmunizationRecordService $immunizations) {}

    public static function model(): string
    {
        return ImmunizationRecord::class;
    }

    public static function label(): string
    {
        return 'immunization';
    }

    public function fields(): array
    {
        return ['id', 'patient_id', 'vaccine_id', 'dose_sequence', 'status', 'due_date', 'administered_date', 'batch_lot', 'site',
            'route', 'reason', 'appointment_id', 'created_at'];
    }

    public function filters(): array
    {
        return ['patient_id' => 'patient_id', 'vaccine_id' => 'vaccine_id', 'status' => 'status'];
    }

    public function sortable(): array
    {
        return ['due_date', 'administered_date', 'created_at'];
    }

    /**
     * Record the dose as given.
     *
     * @bodyParam administered_date string Example: 2026-11-23
     * @bodyParam batch_lot string Example: BCG-2211
     * @bodyParam site string Example: left upper arm
     * @bodyParam route string Example: intradermal
     */
    public function administer(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'administered_date' => ['nullable', 'date', 'before_or_equal:today'],
            'batch_lot' => ['nullable', 'string', 'max:100'],
            'site' => ['nullable', 'string', 'max:100'],
            'route' => ['nullable', 'string', 'max:50'],
            'medication_id' => ['nullable', 'uuid'],
        ]);

        return $this->act($id, 'update', function (ImmunizationRecord $record) use ($data): ImmunizationRecord {
            $this->requireStatus($record, [ImmunizationStatus::SCHEDULED, ImmunizationStatus::DECLINED], 'Only scheduled or declined doses can be administered.');

            try {
                return $this->immunizations->administer($record, $data);
            } catch (QueryException $e) {
                throw $e;
            } catch (\RuntimeException $e) {
                throw new \DomainException($e->getMessage(), 0, $e);
            }
        });
    }

    /**
     * Record the dose as declined.
     *
     * @bodyParam reason string required Example: Parent refused.
     */
    public function decline(Request $request, string $id): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);

        return $this->act($id, 'update', function (ImmunizationRecord $record) use ($data): ImmunizationRecord {
            $this->requireStatus($record, [ImmunizationStatus::SCHEDULED], 'Only scheduled doses can be declined.');

            return $this->immunizations->decline($record, $data);
        });
    }
}
