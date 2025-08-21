<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ControlMaletaResource\Pages;
use App\Filament\Resources\ControlMaletaResource\RelationManagers;
use App\Filament\Resources\ControlMaletaResource\RelationManagers\DetallesRelationManager;
use App\Models\ControlMaleta;
use App\Models\Maleta;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class ControlMaletaResource extends Resource
{
    protected static ?string $model = ControlMaleta::class;

    protected static ?string $navigationGroup = 'Pruebas de herramientas';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                DateTimePicker::make('fecha')
                    ->label('Fecha')
                    ->seconds(false)
                    ->default(now())
                    ->required(),
                TextInput::make('responsable_nombre')
                    ->label('Responsable')
                    ->readOnly()
                    ->dehydrated(false)
                    ->default(fn() => Auth::user()?->name),
                Hidden::make('responsable_id')
                    ->default(fn() => Auth::id())
                    ->required(),
                Select::make('maleta_id')
                    ->label('Maleta')
                    ->relationship('maleta', 'codigo')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->reactive()
                    ->hiddenOn('edit')
                    ->afterStateUpdated(function (?int $state, Set $set) {
                        if ($state) {
                            $maleta = Maleta::with('propietario')->find($state);
                            $set('propietario_id', $maleta?->propietario_id);
                            $set('propietario_nombre', $maleta?->propietario?->name);
                        } else {
                            $set('propietario_id', null);
                            $set('propietario_nombre', null);
                        }
                    }),
                TextInput::make('maleta_codigo')
                    ->label('Maleta')
                    ->readOnly()
                    ->hiddenOn('create'),
                TextInput::make('propietario_nombre')
                    ->label('Propietario')
                    ->readOnly()
                    ->dehydrated(false),
                Hidden::make('propietario_id'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->dateTime('d/m/Y')
                    ->sortable(),

                TextColumn::make('responsable.name')
                    ->label('Responsable')
                    ->sortable()
                    ->searchable(isIndividual: true),

                TextColumn::make('maleta.codigo')
                    ->label('Maleta')
                    ->sortable()
                    ->searchable(isIndividual: true),

                TextColumn::make('propietario.name')
                    ->label('Propietario')
                    ->sortable()
                    ->searchable(isIndividual: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
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
            'index' => Pages\ListControlMaletas::route('/'),
            'create' => Pages\CreateControlMaleta::route('/create'),
            'edit' => Pages\EditControlMaleta::route('/{record}/edit'),
        ];
    }
}
