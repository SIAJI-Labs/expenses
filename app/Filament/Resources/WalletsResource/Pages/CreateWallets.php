<?php

namespace App\Filament\Resources\WalletsResource\Pages;

use App\Filament\Resources\WalletsResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

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
}
