<?php

namespace App\Filament\Resources\WalletGroupsResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class WalletRelationManager extends RelationManager
{
    protected static string $relationship = 'wallet';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Select::make('wallet_id')
                //     ->label('Wallet')
                //     ->options(function(){
                //         return \App\Models\Wallet::with(['parent', 'walletBalance'])
                //             ->orderBy('order_main', 'asc')
                //             ->get()
                //             ->pluck('name_with_parent', 'id')
                //             ->all();
                //     })
                //     ->multiple()
                //     ->native(false)
                //     ->searchable()
                //     ->preload()
                //     ->searchDebounce(500)
                //     ->createOptionForm([
                //         Select::make('parent_id')
                //             ->label('Parent')
                //             ->columnSpanFull()
                //             ->options(function(){
                //                 return \App\Models\Wallet::whereNull('parent_id')
                //                     ->orderBy('order_main', 'asc')
                //                     ->get()
                //                     ->pluck('name', 'id')
                //                     ->all();
                //             })
                //             ->preload()
                //             ->native(false)
                //             ->searchable(),
                //         TextInput::make('name')
                //             ->placeholder('Name')
                //             ->required()
                //             ->maxLength(191),
                //         TextInput::make('initial_balance')
                //             ->placeholder('Initial Balance')
                //             ->required()
                //             ->mask(RawJs::make('$money($input)'))
                //             ->stripCharacters(',')
                //             ->numeric()
                //             ->default(0)
                //     ])
                //     ->createOptionUsing(function (array $data): \App\Models\Wallet {
                //         // Add the request_id if available
                //         $request_id = request()->header('request-id', null);
                //         $data['request_id'] = $request_id;
                //         $data['user_id'] = \Illuminate\Support\Facades\Auth::user()->id;

                //         // Check for existing wallet with the same request_id
                //         $existingWallet = !empty($data['request_id']) 
                //             ? \App\Models\Wallet::where(DB::raw('BINARY `request_id`'), $data['request_id'])->first()
                //             : null;

                //         // If wallet doesn't exist, create a new one
                //         if (!$existingWallet) {
                //             return \App\Models\Wallet::create($data);
                //         }

                //         // If wallet exists with the same request_id, return the existing one
                //         return $existingWallet;
                //     })
                //     ->required()
                //     ->columnSpanFull()
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('wallet_id')
            ->columns([
                Tables\Columns\TextColumn::make('name_with_parent')
                    ->label('Name'),
                Tables\Columns\TextColumn::make('final_balance')
                    ->label('Balance')
                    ->numeric()
                    ->formatStateUsing(fn (string $state): string => number_format($state, 2))
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
                Tables\Actions\AttachAction::make()
                    ->form([
                        Select::make('recordId')
                            ->label('Wallet')
                            ->options(function(){
                                return \App\Models\Wallet::with(['parent', 'walletBalance'])
                                    ->orderBy('order_main', 'asc')
                                    ->get()
                                    ->pluck('name_with_parent', 'id')
                                    ->all();
                            })
                            ->multiple()
                            ->native(false)
                            ->searchable()
                            ->preload()
                            ->searchDebounce(500)
                            ->createOptionForm([
                                Select::make('parent_id')
                                    ->label('Parent')
                                    ->columnSpanFull()
                                    ->options(function(){
                                        return \App\Models\Wallet::whereNull('parent_id')
                                            ->orderBy('order_main', 'asc')
                                            ->get()
                                            ->pluck('name', 'id')
                                            ->all();
                                    })
                                    ->preload()
                                    ->native(false)
                                    ->searchable(),
                                TextInput::make('name')
                                    ->placeholder('Name')
                                    ->required()
                                    ->maxLength(191),
                                TextInput::make('initial_balance')
                                    ->placeholder('Initial Balance')
                                    ->required()
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->numeric()
                                    ->default(0)
                            ])
                            ->createOptionUsing(function (array $data): \App\Models\Wallet {
                                // Add the request_id if available
                                $request_id = request()->header('request-id', null);
                                $data['request_id'] = $request_id;
                                $data['user_id'] = \Illuminate\Support\Facades\Auth::user()->id;

                                // Check for existing wallet with the same request_id
                                $existingWallet = !empty($data['request_id']) 
                                    ? \App\Models\Wallet::where(DB::raw('BINARY `request_id`'), $data['request_id'])->first()
                                    : null;

                                // If wallet doesn't exist, create a new one
                                if (!$existingWallet) {
                                    return \App\Models\Wallet::create($data);
                                }

                                // If wallet exists with the same request_id, return the existing one
                                return $existingWallet;
                            })
                            ->required()
                            ->columnSpanFull()
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
