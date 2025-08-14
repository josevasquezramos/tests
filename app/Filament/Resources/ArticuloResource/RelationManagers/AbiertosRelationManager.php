<?php

namespace App\Filament\Resources\ArticuloResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AbiertosRelationManager extends RelationManager
{
    protected static string $relationship = 'abiertos';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('restante')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('restante')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID Correlativo'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d/m/Y h:i:s A')
                    ->label('Abierto'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime('d/m/Y h:i:s A')
                    ->label('Último consumo'),
                Tables\Columns\TextColumn::make('restante'),
                Tables\Columns\TextColumn::make('articulo.unidad.abreviatura'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
