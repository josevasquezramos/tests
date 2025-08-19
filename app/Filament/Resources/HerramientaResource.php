<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HerramientaResource\Pages;
use App\Filament\Resources\HerramientaResource\RelationManagers;
use App\Models\Herramienta;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
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

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nombre')
                    ->required(),
                TextInput::make('costo')
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
                    ->minValue(0),
                TextInput::make('mermas')
                    ->required()
                    ->default(0)
                    ->numeric()
                    ->minValue(0),
                TextInput::make('perdidas')
                    ->required()
                    ->default(0)
                    ->numeric()
                    ->minValue(0),
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
                    TextColumn::make('cantidad')
                        ->alignEnd()
                        ->label('Cantidad')
                        ->numeric()
                        ->sortable(),
                ]),
                ColumnGroup::make('Ubicación', [
                    TextColumn::make('stock')
                        ->alignEnd()
                        ->label('Almacén')
                        ->sortable(),
                    TextColumn::make('asignadas')
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
