<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaletaResource\Pages;
use App\Filament\Resources\MaletaResource\RelationManagers;
use App\Filament\Resources\MaletaResource\RelationManagers\DetallesRelationManager;
use App\Models\Maleta;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MaletaResource extends Resource
{
    protected static ?string $model = Maleta::class;

    protected static ?string $navigationGroup = 'Pruebas de herramientas';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('codigo')
                    ->required()
                    ->unique(ignoreRecord: true),
                Select::make('propietario_id')
                    ->required()
                    ->relationship('propietario', 'name')
                    ->searchable()
                    ->preload()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')
                    ->sortable()
                    ->searchable(isIndividual: true),
                TextColumn::make('propietario.name')
                    ->sortable()
                    ->searchable(isIndividual: true),
                TextColumn::make('detalles_count')
                    ->label('Herramientas')
                    ->counts('detalles')
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
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
            DetallesRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMaletas::route('/'),
            'create' => Pages\CreateMaleta::route('/create'),
            'edit' => Pages\EditMaleta::route('/{record}/edit'),
        ];
    }
}
