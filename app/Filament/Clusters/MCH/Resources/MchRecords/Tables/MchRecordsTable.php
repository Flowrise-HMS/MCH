<?php

namespace Modules\MCH\Filament\Clusters\MCH\Resources\MchRecords\Tables;

use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Modules\MCH\Classes\Services\MchBookIssuanceService;
use Modules\MCH\Enums\MchRecordStatus;
use Modules\MCH\Models\MchRecord;

class MchRecordsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('serial_number')->searchable()->sortable()->weight('bold'),
                TextColumn::make('unit')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('owner_type')->label('Owner type')->formatStateUsing(
                    fn (?string $state): string => class_basename((string) $state),
                ),
                TextColumn::make('issue_date')->date()->sortable(),
                IconColumn::make('data_consented')->boolean()->label('Consent'),
            ])
            ->filters([
                SelectFilter::make('status')->options(MchRecordStatus::class),
                SelectFilter::make('unit')->options(['ANC' => 'ANC', 'CWC' => 'CWC']),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('replace')
                    ->label('Replace book')
                    ->icon('heroicon-m-arrow-path')
                    ->requiresConfirmation()
                    ->visible(fn (MchRecord $record): bool => $record->status === MchRecordStatus::ACTIVE)
                    ->authorize(fn (MchRecord $record): bool => auth()->user()?->can('update', $record) ?? false)
                    ->action(function (MchRecord $record): void {
                        app(MchBookIssuanceService::class)->replace($record, auth()->user());
                        Notification::make()->title('Replacement book issued')->success()->send();
                    }),
                EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
