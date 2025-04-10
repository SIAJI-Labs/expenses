<?php

namespace App\Filament\Resources\TagsResource\Pages;

use App\Filament\Resources\TagsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTags extends EditRecord
{
    protected static string $resource = TagsResource::class;

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
