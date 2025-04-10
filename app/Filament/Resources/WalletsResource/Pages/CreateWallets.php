<?php

namespace App\Filament\Resources\WalletsResource\Pages;

use Illuminate\Support\Facades\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use App\Filament\Resources\WalletsResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Throwable;

class CreateWallets extends CreateRecord
{
    protected static string $resource = WalletsResource::class;

    protected function getRedirectUrl(): string
    {
        $resource = static::getResource();
        
        return $resource::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $request_id = Request::header('request-id', null);

        $data['request_id'] = $request_id;
        $data['user_id'] = \Illuminate\Support\Facades\Auth::user()->id;

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Check for request_id columns
        $exists = !empty($data['request_id']) ? \App\Models\Wallet::where(DB::raw('BINARY `request_id`'), $data['request_id'])->first() : null;
        $model = $exists;
        if(empty($model)){
            $model = static::getModel()::create($data);
        }

        return $model;
    }

    /**
     * Override Create function from CreateRecord
     */
    public function create(bool $another = false): void
    {
        $this->authorizeAccess();

        try {
            $this->beginDatabaseTransaction();

            $this->callHook('beforeValidate');

            $data = $this->form->getState();

            $this->callHook('afterValidate');

            $data = $this->mutateFormDataBeforeCreate($data);

            $this->callHook('beforeCreate');

            $this->record = $this->handleRecordCreation($data);

            $this->form->model($this->getRecord())->saveRelationships();

            $this->callHook('afterCreate');
        } catch (Halt $exception) {
            $exception->shouldRollbackDatabaseTransaction()
                ? $this->rollBackDatabaseTransaction()
                : $this->commitDatabaseTransaction();

            return;
        } catch (Throwable $exception) {
            $this->rollBackDatabaseTransaction();

            throw $exception;
        }

        $this->commitDatabaseTransaction();

        $this->getCreatedNotification()?->send();

        if ($another) {
            $state = [];
            // Keep selected parent
            $state['parent_id'] = $data['parent_id'];

            // Prepare a fresh form
            $this->record = null;
            $this->form->model($this->getModel())->fill($state);

            return;
        }

        $this->redirect($this->getRedirectUrl());
    }
}
