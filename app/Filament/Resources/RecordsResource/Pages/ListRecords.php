<?php

namespace App\Filament\Resources\RecordsResource\Pages;

use App\Filament\Resources\RecordsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords as FilamentListRecords;

class ListRecords extends FilamentListRecords
{
    protected static string $resource = RecordsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
