<?php

use Modules\Core\Services\ApiRouteRegistrar;
use Modules\MCH\Http\Controllers\Api\AncVisitController;
use Modules\MCH\Http\Controllers\Api\ChildHealthRecordController;
use Modules\MCH\Http\Controllers\Api\CwcVisitController;
use Modules\MCH\Http\Controllers\Api\DeliveryRecordController;
use Modules\MCH\Http\Controllers\Api\GrowthMeasurementController;
use Modules\MCH\Http\Controllers\Api\ImmunizationController;
use Modules\MCH\Http\Controllers\Api\MchBookController;
use Modules\MCH\Http\Controllers\Api\PncVisitController;
use Modules\MCH\Http\Controllers\Api\PregnancyEpisodeController;
use Modules\MCH\Http\Controllers\Api\VaccineController;

/*
| MCH REST API (`/api/v1`, Sanctum + branch, token scope "mch"). Client data is
| limited to clients with data-sharing consent (MchConsentGate) unless the
| caller has mch_override_consent.
*/
ApiRouteRegistrar::register(scope: 'mch', routes: function (): void {
    PregnancyEpisodeController::routes('pregnancies', ['index', 'show', 'store', 'update']);
    ChildHealthRecordController::routes('child-health-records', ['index', 'show', 'store', 'update']);
    AncVisitController::routes('anc-visits', ['index', 'show', 'store']);
    CwcVisitController::routes('cwc-visits', ['index', 'show', 'store']);
    PncVisitController::routes('pnc-visits', ['index', 'show', 'store']);
    GrowthMeasurementController::routes('growth-measurements', ['index', 'show', 'store']);
    ImmunizationController::routes('immunizations', ['index', 'show'], ['administer' => 'administer', 'decline' => 'decline']);
    VaccineController::routes('vaccines', ['index', 'show']);
    DeliveryRecordController::routes('deliveries', ['index', 'show', 'store']);
    MchBookController::routes('mch-books', ['index', 'show'], ['record-consent' => 'recordConsent']);
});
