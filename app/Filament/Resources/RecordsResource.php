<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RecordsResource\Pages;
use App\Filament\Resources\RecordsResource\RelationManagers;
use App\Models\Category;
use App\Models\Records;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RecordsResource extends Resource
{
    protected static ?string $model = \App\Models\Record::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $activeNavigationIcon = 'heroicon-s-book-open';

    // Update Final Amount attribute
    private static function updateFinalAmount(Set $set, Get $get): void
    {
        // Get the raw numeric values, sanitized from input
        $amount = (int) filter_var($get('amount'), FILTER_SANITIZE_NUMBER_INT) ?? 0;
        $extra_amount = (int) filter_var($get('charge'), FILTER_SANITIZE_NUMBER_INT) ?? 0;
        $extra_type = $get('extra_type'); // amount / percentage

        // Override extra_amount when extra_type is percentage
        if ($extra_type === 'percentage') {
            $set('extra_percentage', $extra_amount);
            $extra_amount = (($extra_amount * $amount) / 100);
        }

        // Calculate the final amount
        $final_amount = $amount + $extra_amount;
        $formatted_final_amount = number_format($final_amount, 0, '.', ',');

        // Update extra-amount
        $set('extra_amount', $extra_amount);

        // Set the final amount, formatted with commas
        $set('final_amount', $formatted_final_amount);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                ToggleButtons::make('type')
                    ->label('Type')
                    ->options([
                        'expense' => 'Expense',
                        'transfer' => 'Transfer',
                        'income' => 'Income'
                    ])
                    ->inline()
                    ->required()
                    ->columnSpanFull()
                    ->default('expense')
                    ->in(['expense', 'transfer', 'income']),
                Select::make('category_id')
                    ->relationship(
                        name: 'category',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn(Builder $query) => $query->orderBy('order_main', 'asc')
                    )
                    ->getOptionLabelFromRecordUsing(fn (Model $record) => $record->name_with_parent)
                    ->preload()
                    ->createOptionForm([
                        Select::make('parent_id')
                            ->label('Parent')
                            ->columnSpanFull()
                            ->options(function(){
                                return \App\Models\Category::whereNull('parent_id')
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
                            ->maxLength(191)
                            ->columnSpanFull(),
                    ])
                    ->createOptionUsing(function (array $data): \App\Models\Category {
                        // Add the request_id if available
                        $request_id = request()->header('request-id', null);
                        $data['request_id'] = $request_id;
                        $data['user_id'] = \Illuminate\Support\Facades\Auth::user()->id;

                        // Check for existing category with the same request_id
                        $existingCategory = !empty($data['request_id']) 
                            ? \App\Models\Category::where(DB::raw('BINARY `request_id`'), $data['request_id'])->first()
                            : null;

                        // If category doesn't exist, create a new one
                        if (!$existingCategory) {
                            return \App\Models\Category::create($data);
                        }

                        // If category exists with the same request_id, return the existing one
                        return $existingCategory;
                    })
                    ->searchDebounce(500)
                    ->native(false)
                    ->searchable()
                    ->columnSpanFull(),

                // Wallets
                Select::make('from_wallet_id')
                    ->label('Source')
                    ->relationship(
                        name: 'fromWallet',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn(Builder $query) => $query->orderBy('order_main', 'asc')
                    )
                    ->getOptionLabelFromRecordUsing(fn (Model $record) => $record->name_with_parent)
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
                    ->required(),
                Select::make('to_wallet_id')
                    ->label('Target')
                    ->placeholder('Select an option')
                    ->relationship(
                        name: 'toWallet',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn(Builder $query) => $query->orderBy('order_main', 'asc')
                    )
                    ->getOptionLabelFromRecordUsing(fn (Model $record) => $record->name_with_parent)
                    ->native(false)
                    ->preload()
                    ->searchable()
                    ->requiredIf('type', 'transfer')
                    ->suffixAction(
                        Action::make('switch')
                            ->icon('heroicon-o-arrow-path-rounded-square')
                            ->action(function (Set $set, $state, $get) {
                                // Access the current values from both 'from_wallet_id' and 'to_wallet_id' fields
                                $fromWalletId = $get('from_wallet_id');
                                $toWalletId = $get('to_wallet_id');
                
                                // Switch the values between the two fields
                                $set('from_wallet_id', $toWalletId);
                                $set('to_wallet_id', $fromWalletId);
                            })
                            ->disabled(fn ($get) => $get('type') !== 'transfer')
                    )
                    ->disabled(fn ($get) => $get('type') !== 'transfer')
                    ->afterStateUpdated(function ($state, $get, $set) {
                        // Check if the from_wallet_id and to_wallet_id are the same
                        $fromWallet = $get('from_wallet_id');
                        $toWallet = $get('to_wallet_id');
                        
                        if ($fromWallet === $toWallet) {
                            $set('to_wallet_id', null);
                            Notification::make()
                                ->warning()
                                ->title('Action failed!')
                                ->body('The source and target wallets must be different.')
                                ->send();
                            return null;
                        }
                    }),

                // Timestamp
                DateTimePicker::make('timestamp')
                    ->label('Timestamp')
                    ->placeholder('Select date and time')
                    ->displayFormat('d F, Y / H:i')
                    ->seconds(false)
                    ->native(false)
                    ->maxDate(\Carbon\Carbon::now()->setHour(23)->setMinute(59))
                    ->columnSpanFull()
                    ->required(),

                // Amount
                TextInput::make('amount')
                    ->placeholder('Input numeric value')
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->numeric()
                    ->afterStateUpdated(function ($state, $get, $set) {
                        // Update Final Amount
                        self::updateFinalAmount($set, $get);
                    })
                    ->default(0)
                    ->required()
                    ->live(debounce: 500),
                TextInput::make('charge')
                    ->label('Extra amount')
                    ->placeholder('Input numeric value')
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->numeric()
                    ->afterStateUpdated(function ($state, $get, $set) {
                        // Update Final Amount
                        self::updateFinalAmount($set, $get);
                    })
                    ->default(0)
                    ->suffixAction(
                        Action::make('extra_percentage')
                            ->icon('tabler-percentage')
                            ->color(fn(Get $get) => $get('extra_type') === 'percentage' ? 'primary' : 'gray')
                            ->action(function (Set $set, $state, $get) {
                                // Toggle the `extra_type` between 'amount' and 'percentage'
                                $set('extra_type', $get('extra_type') === 'percentage' ? 'amount' : 'percentage');
                                if($get('extra_type') === 'percentage'){
                                    $set('extra_percentage', $get('charge'));
                                } else {
                                    $set('extra_percentage', null);
                                }

                                // Update Final Amount
                                self::updateFinalAmount($set, $get);
                            })
                    )
                    ->live(debounce: 500),
                TextInput::make('final_amount')
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->numeric()
                    ->disabled()
                    ->default(0)
                    ->columnSpanFull(),
                Hidden::make('extra_type')
                    ->default('amount')
                    ->in(['amount', 'percentage']),
                Hidden::make('extra_amount')
                    ->default(0),
                Hidden::make('extra_percentage')
                    ->default(0),

                // Notes
                MarkdownEditor::make('notes')
                    ->toolbarButtons([
                        'blockquote',
                        'bold',
                        'bulletList',
                        'codeBlock',
                        'heading',
                        'italic',
                        'link',
                        'orderedList',
                        'redo',
                        'strike',
                        'undo',
                    ])
                    ->columnSpanFull(),

                Select::make('tags_id')
                    ->label('Tags')
                    ->relationship(
                        name: 'tags',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn(Builder $query) => $query->orderBy('name', 'asc')
                    )
                    ->multiple()
                    ->native(false)
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->placeholder('Name')
                            ->required()
                            ->maxLength(191)
                            ->columnSpanFull(),
                    ])
                    ->createOptionUsing(function (array $data): \App\Models\Tag {
                        // Add the request_id if available
                        $request_id = request()->header('request-id', null);
                        $data['request_id'] = $request_id;
                        $data['user_id'] = \Illuminate\Support\Facades\Auth::user()->id;

                        // Check for existing tag with the same request_id
                        $existingTag = !empty($data['request_id']) 
                            ? \App\Models\Tag::where(DB::raw('BINARY `request_id`'), $data['request_id'])->first()
                            : null;

                        // If tag doesn't exist, create a new one
                        if (!$existingTag) {
                            return \App\Models\Tag::create($data);
                        }

                        // If tag exists with the same request_id, return the existing one
                        return $existingTag;
                    })
                    ->searchable()
                    ->columnSpanFull(),
            ])
            ->reactive();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'expense' => 'danger',
                        'transfer' => 'gray',
                        'income' => 'success',
                    })
                    ->formatStateUsing(fn (string $state): string => ucwords($state))
                    ->description(fn ($record) => $record->category?->name_with_parent ?? 'Uncategorized'),
                TextColumn::make('fromWallet.name_with_parent')
                    ->label('Source'),
                TextColumn::make('toWallet.name_with_parent')
                    ->label('Target')
                    ->default('-'),
                TextColumn::make('amount')
                    ->label('Amount')
                    ->color(fn ($record) => match($record->type){
                        'income' => 'success',
                        'expense' => 'danger',
                        default => 'gray'
                    })
                    ->numeric()
                    ->formatStateUsing(function ($record) {
                        return number_format($record->amount + $record->extra_amount, 2);
                    })
                    ->description(function ($record) {
                        return $record->extra_amount ? 'Extra: ' . number_format($record->extra_amount, 2).(!empty($record->extra_percentage) ? ' ('.$record->extra_percentage.'%)' : null) : null;
                    })
                    ->sortable(),
                TextColumn::make('timestamp')
                    ->dateTime('d M, Y / H:i')
                    ->sortable()
                    ->description(function ($record) {
                        return $record->is_hidden ? '[hidden]' : null;
                    }),
                TextColumn::make('tags_count')
                    ->label('Tags')
                    ->counts('tags')
                    ->badge()
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Type')
                    ->options([
                        'expense' => 'Expense',
                        'transfer' => 'Transfer',
                        'income' => 'Income'
                    ])
                    ->native(false)
                    ->searchable(),
                SelectFilter::make('category_id')
                    ->label('Category')
                    ->options(function () {
                        $categories = \App\Models\Category::with(['parent'])
                            ->orderBy('order_main', 'asc')
                            ->get()
                            ->pluck('name_with_parent', 'id')
                            ->all();
                
                        // Add "Uncategorized" with a fake ID like 'uncategorized-9hpwQ1Nsga' for records with null value of category_id
                        return ['uncategorized-9hpwQ1Nsga' => 'Uncategorized'] + $categories;
                    })
                    ->native(false)
                    ->searchable()
                    ->multiple()
                    ->preload()
                    ->query(function (Builder $query, array $data) {
                        // If no category is selected, return all records (when 'values' is empty)
                        if (empty($data['values'])) {
                            return $query;  // Return the query unmodified to show all records
                        }

                        // Apply filtering for categories
                        $categoryIds = $data['values'];

                        // Check if "Uncategorized" is selected
                        $uncategorizedSelected = in_array('uncategorized-9hpwQ1Nsga', $categoryIds);

                        // If "Uncategorized" is selected, filter records with category_id is null
                        if ($uncategorizedSelected) {
                            $query->whereNull('category_id');
                        }

                        // Remove the "Uncategorized" option from the category IDs to filter the remaining categories
                        $categoryIds = array_diff($categoryIds, ['uncategorized-9hpwQ1Nsga']);

                        // Apply filtering for other selected categories if there are any
                        if (!empty($categoryIds)) {
                            $query->orWhereIn('category_id', $categoryIds);
                        }

                        return $query;
                    }),
                SelectFilter::make('from_wallet_id')
                    ->label('Source')
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
                    ->preload(),
                SelectFilter::make('to_wallet_id')
                    ->label('Target')
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
                    ->preload(),
                SelectFilter::make('tags')
                    ->label('Tags')
                    ->options(function(){
                        return \App\Models\Tag::orderBy('name', 'asc')
                            ->get()
                            ->pluck('name', 'id')
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
                        $query->whereHas('tags', function (Builder $query) use ($data) {
                            $query->whereIn('tags.id', $data['values']); // Use the 'values' key here
                        });
                    }),
                TernaryFilter::make('is_hidden')
                    ->label('Is hidden')
                    ->placeholder('Only Visible')
                    ->trueLabel('Only Hidden')
                    ->falseLabel('All records')
                    ->queries(
                        true: fn (Builder $query) => $query->where('is_hidden', true),
                        false: fn (Builder $query) => $query->whereIn('is_hidden', [false, true]),
                        blank: fn (Builder $query) => $query->where('is_hidden', false),
                    )
            ])
            ->deselectAllRecordsWhenFiltered(true)
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('timestamp', 'desc');
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
            'index' => Pages\ListRecords::route('/'),
            'create' => Pages\CreateRecords::route('/create'),
            'edit' => Pages\EditRecords::route('/{record}/edit'),
        ];
    }
}
