<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HerramientaResource\Pages;
use App\Filament\Resources\HerramientaResource\RelationManagers;
use App\Models\Herramienta;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class HerramientaResource extends Resource
{
    protected static ?string $model = Herramienta::class;

    protected static ?string $navigationGroup = 'Pruebas de herramientas';

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?int $navigationSort = 90;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nombre')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('costo')
                    ->default(0)
                    ->required()
                    ->prefix('S/')
                    ->numeric()
                    ->minValue(0),
                TextInput::make('stock')
                    ->required()
                    ->default(0)
                    ->numeric()
                    ->minValue(0),
                TextInput::make('asignadas')
                    ->required()
                    ->default(0)
                    ->numeric()
                    ->minValue(0)
                    ->hiddenOn('create'),
                TextInput::make('mermas')
                    ->required()
                    ->default(0)
                    ->numeric()
                    ->minValue(0)
                    ->hiddenOn('create'),
                TextInput::make('perdidas')
                    ->required()
                    ->default(0)
                    ->numeric()
                    ->minValue(0)
                    ->hiddenOn('create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ColumnGroup::make('Herramienta', [
                    TextColumn::make('nombre')
                        ->searchable(isIndividual: true)
                        ->sortable(),
                    TextColumn::make('costo')
                        ->alignEnd()
                        ->prefix('S/ ')
                        ->sortable(),
                ]),
                ColumnGroup::make('Stock', [
                    TextColumn::make('cantidad')
                        ->alignEnd()
                        ->weight(FontWeight::Bold)
                        ->label('Total')
                        ->numeric()
                        ->sortable(),
                    TextColumn::make('stock')
                        ->alignEnd()
                        ->label('Almacén')
                        ->sortable(),
                    TextColumn::make('asignadas')
                        ->label('Maletas')
                        ->alignEnd()
                        ->sortable(),
                ]),
                TextColumn::make('mermas')
                    ->alignEnd()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('perdidas')
                    ->alignEnd()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHerramientas::route('/'),
            'create' => Pages\CreateHerramienta::route('/create'),
            'edit' => Pages\EditHerramienta::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->select('herramientas.*')
            ->selectRaw('(stock + asignadas) as cantidad');
    }
}
