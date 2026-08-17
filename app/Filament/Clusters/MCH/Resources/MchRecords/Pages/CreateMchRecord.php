<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\MchRecords\Pages;

use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;
use Modules\Core\Models\Branch;
use Modules\MCH\Classes\Services\MchBookIssuanceService;
use Modules\MCH\Filament\Clusters\MCH\Resources\MchRecords\MchRecordResource;
use Modules\MCH\Models\ChildHealthRecord;
use Modules\MCH\Models\PregnancyEpisode;

class CreateMchRecord extends CreateRecord
{
    protected static string $resource = MchRecordResource::class;

    /**
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $ownerClass = $data['owner_type'];

        if (! in_array($ownerClass, [PregnancyEpisode::class, ChildHealthRecord::class], true)) {
            throw new \InvalidArgumentException('MCH books can only be issued to a pregnancy episode or child health record.');
        }

        $owner = $ownerClass::query()->findOrFail($data['owner_id']);

        $branchId = Context::get('current_branch_id') ?? Auth::user()?->branch_id;
        $branch = Branch::query()->findOrFail($branchId);

        $user = Auth::user();

        return app(MchBookIssuanceService::class)->issue(
            $owner,
            $branch,
            $data['unit'],
            ['data_consented' => (bool) ($data['data_consented'] ?? false)],
            $user instanceof User ? $user : null,
        );
    }
}
