<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletGroupsResource\Pages;
use App\Filament\Resources\WalletGroupsResource\RelationManagers;
use App\Models\WalletGroups;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class WalletGroupsResource extends Resource
{
    protected static ?string $model = \App\Models\WalletGroup::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->placeholder('Name')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('wallet_count')
                    ->label('Wallet')
                    ->counts('wallet')
                    ->badge()
                    ->sortable(),
                TextColumn::make('accumulated_balance')
                    ->label('Accumulated Balance')
                    ->default('-')
                    ->formatStateUsing(function ($record) {
                        $balance = 0;
                        if($record->wallet->count() > 0){
                            foreach($record->wallet as $wallet){
                                $balance += $wallet->final_balance;
                            }
                        }

                        return number_format($balance, 2);
                    })
            ])
            ->filters([
                SelectFilter::make('wallet')
                    ->label('Wallet')
                    ->options(function(){
                        return \App\Models\Wallet::with(['parent'])
                            ->orderBy('order_main', 'asc')
                            ->get()
                            ->pluck('name_with_parent', 'id')
                            ->all();
                    })
                    ->native(false)
                    ->searchable()
                    ->multiple()
                    ->preload()
                    ->query(function (Builder $query, array $data) {
                        // If no tags are selected, return all records (when 'values' is empty)
                        if (empty($data['values'])) {
                            return $query;  // Return the query unmodified to show all records
                        }

                        // Apply filtering when tags are selected
                        $query->whereHas('wallet', function (Builder $query) use ($data) {
                            $query->whereIn('wallet_id', $data['values']); // Use the 'values' key here
                        });
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\WalletRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWalletGroups::route('/'),
            'create' => Pages\CreateWalletGroups::route('/create'),
            'edit' => Pages\EditWalletGroups::route('/{record}/edit'),
            'view' => Pages\ViewWalletGroups::route('/{record}'),
        ];
    }
}
