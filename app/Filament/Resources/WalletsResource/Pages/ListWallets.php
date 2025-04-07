<?php

namespace App\Filament\Resources\WalletsResource\Pages;

use App\Filament\Resources\WalletsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListWallets extends ListRecords
{
    protected static string $resource = WalletsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
