<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoriesResource\Pages;
use App\Filament\Resources\CategoriesResource\RelationManagers;
use App\Models\Categories;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CategoriesResource extends Resource
{
    protected static ?string $model = \App\Models\Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-bookmark';
    protected static ?string $activeNavigationIcon = 'heroicon-s-bookmark';
    protected static ?string $navigationGroup = 'Master Data';

    public static function form(Form $form): Form
    {
        return $form
        ->schema([
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
                TextColumn::make('order_main')
                    ->label('Order')
                    ->sortable()
            ])
            ->filters([
                SelectFilter::make('parent_id')
                    ->label('Parent')
                    ->options(function(){
                        return \App\Models\Category::whereNull('parent_id')
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
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategories::route('/create'),
            'edit' => Pages\EditCategories::route('/{record}/edit'),
        ];
    }
}
