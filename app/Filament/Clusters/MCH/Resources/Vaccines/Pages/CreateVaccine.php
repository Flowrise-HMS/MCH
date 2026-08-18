<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\Vaccines\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\MCH\Filament\Clusters\MCH\Resources\Vaccines\VaccineResource;

class CreateVaccine extends CreateRecord
{
    protected static string $resource = VaccineResource::class;
}
