<?php

namespace Modules\MCH\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\Api\ResourceApiController;
use Modules\MCH\Classes\Services\MchConsentGate;
use Modules\MCH\Models\MchRecord;

/**
 * @group Maternal and child health
 *
 * Client-held MCH books and their data-sharing consent.
 */
class MchBookController extends ResourceApiController
{
    public static function model(): string
    {
        return MchRecord::class;
    }

    public static function label(): string
    {
        return 'MCH book';
    }

    public function fields(): array
    {
        return ['id', 'owner_type', 'owner_id', 'serial_number', 'unit', 'issue_date', 'status', 'data_consented', 'consented_at', 'consented_by'];
    }

    public function filters(): array
    {
        return ['unit' => 'unit', 'status' => 'status', 'data_consented' => 'data_consented', 'serial_number' => 'serial_number'];
    }

    /**
     * Record that the client consented to sharing MCH data.
     */
    public function recordConsent(string $id): JsonResponse
    {
        return $this->act($id, 'update', fn (MchRecord $book) => app(MchConsentGate::class)->recordConsent($book, auth()->id()));
    }
}
