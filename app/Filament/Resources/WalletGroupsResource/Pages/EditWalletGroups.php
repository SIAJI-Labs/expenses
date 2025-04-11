<?php

namespace App\Filament\Resources\WalletGroupsResource\Pages;

use App\Filament\Resources\WalletGroupsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWalletGroups extends EditRecord
{
    protected static string $resource = WalletGroupsResource::class;

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
}
