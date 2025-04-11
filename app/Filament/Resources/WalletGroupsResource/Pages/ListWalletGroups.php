<?php

namespace App\Filament\Resources\WalletGroupsResource\Pages;

use App\Filament\Resources\WalletGroupsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWalletGroups extends ListRecords
{
    protected static string $resource = WalletGroupsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
