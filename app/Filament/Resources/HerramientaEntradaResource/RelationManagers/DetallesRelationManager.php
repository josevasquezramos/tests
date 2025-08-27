<?php

namespace App\Filament\Resources\HerramientaEntradaResource\RelationManagers;

use App\Models\Herramienta;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class DetallesRelationManager extends RelationManager
{
    protected static string $relationship = 'detalles';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('herramienta_id')
                    ->columnSpanFull()
                    ->label('Herramienta')
                    ->relationship('herramienta', 'nombre') // cambia 'nombre' si tu columna difiere
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live() // necesario para reaccionar al cambio
                    ->afterStateUpdated(function ($state, callable $set) {
                        // Cuando el usuario selecciona una herramienta, jalamos su costo
                        $costo = Herramienta::find($state)?->costo ?? null;
                        if (! is_null($costo)) {
                            $set('costo', $costo);
                        }
                    }),

                TextInput::make('cantidad')
                    ->label('Cantidad')
                    ->numeric()
                    ->minValue(1)
                    ->default(1)
                    ->required(),

                TextInput::make('costo')
                    ->label('Costo')
                    ->numeric()
                    ->rule('decimal:0,2')
                    ->required()
                    ->prefix('S/'), // opcional, cambia a tu moneda
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('herramienta_id')
            ->columns([
                TextColumn::make('herramienta.nombre')
                    ->label('Herramienta')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('cantidad')
                    ->label('Cant.')
                    ->sortable(),

                TextColumn::make('costo')
                    ->label('Costo')
                    ->numeric(decimalPlaces: 2)
                    ->prefix('S/')
                    ->sortable(),

                TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->getStateUsing(fn ($record) => (float) $record->cantidad * (float) $record->costo)
                    ->numeric(decimalPlaces: 2)
                    ->prefix('S/')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
}
