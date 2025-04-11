<?php

namespace App\Filament\Resources\WalletGroupsResource\Pages;

use Illuminate\Support\Facades\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use App\Filament\Resources\WalletGroupsResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Throwable;

class CreateWalletGroups extends CreateRecord
{
    protected static string $resource = WalletGroupsResource::class;

    protected function getRedirectUrl(): string
    {
        $resource = static::getResource();

        if ($resource::hasPage('view') && $resource::canView($this->getRecord())) {
            return $resource::getUrl('view', ['record' => $this->getRecord(), ...$this->getRedirectUrlParameters()]);
        }

        if ($resource::hasPage('edit') && $resource::canEdit($this->getRecord())) {
            return $resource::getUrl('edit', ['record' => $this->getRecord(), ...$this->getRedirectUrlParameters()]);
        }

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
}
