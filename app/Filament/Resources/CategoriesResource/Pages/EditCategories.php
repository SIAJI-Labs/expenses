<?php

namespace App\Filament\Resources\CategoriesResource\Pages;

use App\Filament\Resources\CategoriesResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCategories extends EditRecord
{
    protected static string $resource = CategoriesResource::class;

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

    protected function beforeSave(): void
    {
        $data = $this->getRecord();
        if(empty($data->parent_id)){
            $proceed = true;
            $message = 'Something went wrong';

            // Get the new form data
            $form = $this->data;
            if(!empty($form['parent_id'])){
                // Validate self reference
                if((int)$form['parent_id'] === (int)$data->id){
                    $proceed = false;
                    $message = 'Can\'t self references, please select another category as it\'s parent!';
                } else {
                    // Validate if data is used as parent on another category
                    if(count($data->child) > 0){
                        $proceed = false;
                        $message = 'This data is used as parent category on another category, please remove it first!';
                    }
                }
            }

            if(!$proceed){
                Notification::make()
                    ->warning()
                    ->title('Action failed!')
                    ->body($message)
                    ->persistent()
                    ->send();
                    
                $this->halt();
            }
        }
    }
}
