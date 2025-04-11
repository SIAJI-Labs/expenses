<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletsResource\Pages;
use App\Filament\Resources\WalletsResource\RelationManagers;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Filament\Forms\Components\TextInput\Mask;
use Filament\Support\RawJs;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

class WalletsResource extends Resource
{
    protected static ?string $model = \App\Models\Wallet::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';
    protected static ?string $activeNavigationIcon = 'heroicon-s-wallet';
    protected static ?string $navigationGroup = 'Master Data';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('parent.name')
                    ->label('Parent')
                    ->default('-'),
                TextColumn::make('name')
                    ->label('Name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('walletBalance.balance')
                    ->label('Balance')
                    ->numeric()
                    ->formatStateUsing(function ($record) {
                        return number_format($record->walletBalance->balance, 2);
                    })
                    ->description(function ($record) {
                        if($record->child->count() > 0){
                            $accumulated = $record->walletBalance->balance;
                            foreach($record->child as $child){
                                $balance = $child->walletBalance->balance;

                                $accumulated += $balance;
                            }

                            return 'Accumulated: '.number_format($accumulated, 2);
                        }

                        return null;
                    })
                    ->sortable(),
                TextColumn::make('order_main')
                    ->label('Order')
                    ->sortable()
            ])
            ->filters([
                SelectFilter::make('parent_id')
                    ->label('Parent')
                    ->options(function(){
                        return \App\Models\Wallet::whereNull('parent_id')
                            ->orderBy('order_main', 'asc')
                            ->get()
                            ->pluck('name', 'id')
                            ->all();
                    })
                    ->native(false)
                    ->searchable(),
            ])
            ->deselectAllRecordsWhenFiltered(true)
            ->defaultSort('order_main', 'asc')
            ->actions([
                Tables\Actions\Action::make('adjustBalance')
                    ->label('Adjust Balance')
                    ->icon('heroicon-o-currency-dollar')
                    ->form([
                        Forms\Components\TextInput::make('actual')
                            ->label('Actual Balance')
                            ->numeric()
                            ->required()
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->default(fn ($record) => $record->final_balance ?? 0)
                            ->hint(fn(Model $record) => 'Current Balance: '.(number_format($record->final_balance ?? 0, 2))),
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->maxLength(255)
                            ->default('Balance adjustment'),
                        Forms\Components\Toggle::make('is_hidden')
                            ->default(true)
                    ])
                    ->action(function (array $data, $record) {
                        $currentBalance = $record->final_balance;
                        $newBalance = (float) $data['actual'];
                        $note = $data['notes'] ?? null;
                        $is_hidden = $data['is_hidden'] ?? false;

                        $type = 'expense';
                        $difference = $newBalance - $currentBalance;
                        if ($difference > 0) {
                            $type = 'income';
                        } else if($difference < 0) {
                            $difference *= -1;
                        } else {
                            return;

                        }

                        // Create record for related adjustment
                        $request_id = Request::header('request-id', null);
                        $exists = !empty($request_id) ? \App\Models\Record::where(DB::raw('BINARY `request_id`'), $request_id)->first() : null;
                        if(empty($exists)){
                            $data = new \App\Models\Record([
                                'user_id' => \Illuminate\Support\Facades\Auth::user()->id,
                                'type' => $type,
                                'from_wallet_id' => $record->id,
                                'amount' => $difference,
                                'is_hidden' => $is_hidden,
                                'timestamp' => \Carbon\Carbon::now()
                            ]);
                            $data->save();
                        }
                    })
                    ->modalHeading('Adjust Wallet Balance')
                    ->color('info'),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->persistSortInSession()
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add new')
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWallets::route('/'),
            'create' => Pages\CreateWallets::route('/create'),
            'edit' => Pages\EditWallets::route('/{record}/edit'),
        ];
    }
}
