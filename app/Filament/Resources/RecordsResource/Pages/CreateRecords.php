<?php

namespace App\Filament\Resources\RecordsResource\Pages;

use Illuminate\Support\Facades\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use App\Filament\Resources\RecordsResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Throwable;

class CreateRecords extends CreateRecord
{
    protected static string $resource = RecordsResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
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
        $exists = !empty($data['request_id']) ? \App\Models\Record::where(DB::raw('BINARY `request_id`'), $data['request_id'])->first() : null;
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
            // Keep selected data
            $keep = [
                'type',
                'category_id',
                'from_wallet_id',
                'to_wallet_id',
                'tags_id'
            ];
            foreach($keep as $column){
                if(isset($data[$column])){
                    $state[$column] = $data[$column];
                }
            }

            // Update Timestamp
            $state['timestamp'] = \Carbon\Carbon::now()->setTimezone(\Illuminate\Support\Facades\Auth::user()->user_timezone);

            // Prepare a fresh form
            $this->record = null;
            $this->form->model($this->getModel())->fill($state);

            return;
        }

        $this->redirect($this->getRedirectUrl());
    }
}
