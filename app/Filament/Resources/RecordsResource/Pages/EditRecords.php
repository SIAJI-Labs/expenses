<?php

namespace App\Filament\Resources\RecordsResource\Pages;

use App\Filament\Resources\RecordsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRecords extends EditRecord
{
    protected static string $resource = RecordsResource::class;

    protected function getRedirectUrl(): string
    {
        $resource = static::getResource();
        
        return $resource::getUrl('index');
    }

    protected function getHeaderActions(): array
    {
        $resource = static::getResource();

        return [
            Actions\Action::make('back')
                ->url($resource::getUrl('index'))
                ->button()
                ->color('secondary'),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['extra_type'] = !empty($data['extra_percentage']) ? 'percentage' : 'amount';
        $data['charge'] = !empty($data['extra_percentage']) && $data['extra_percentage'] > 0 ? $data['extra_percentage'] : $data['extra_amount'];

        return $data;
    }

}
