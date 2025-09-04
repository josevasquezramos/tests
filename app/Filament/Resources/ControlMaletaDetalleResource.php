<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ControlMaletaDetalleResource\Pages;
use App\Filament\Resources\ControlMaletaDetalleResource\RelationManagers;
use App\Models\ControlMaletaDetalle;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ControlMaletaDetalleResource extends Resource
{
    protected static ?string $model = ControlMaletaDetalle::class;

    protected static ?string $navigationGroup = 'Pruebas de herramientas';

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationLabel = 'Historial de Controles';

    protected static ?string $modelLabel = 'Historial de Controles';

    protected static ?int $navigationSort = 100;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('control.fecha')
                    ->label('Fecha')
                    ->dateTime('d/m/Y h:i A')
                    ->sortable(),
                Tables\Columns\TextColumn::make('control.propietario.name')
                    ->sortable()
                    ->searchable(isIndividual: true)
                    ->label('Técnico')
                    ->placeholder('No asignado'),
                Tables\Columns\TextColumn::make('herramienta.nombre')
                    ->sortable()
                    ->searchable(isIndividual: true),
                Tables\Columns\TextColumn::make('estado')
                    ->label('Caso')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'OPERATIVO' => 'success',
                        'MERMA' => 'warning',
                        'PERDIDO' => 'danger',
                    })
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                // Filtro opcional si quieres permitir refinar aún más
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'MERMA' => 'Merma',
                        'PERDIDO' => 'Perdido',
                    ])
                    ->multiple(),
            ])
            ->actions([
                // Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    // Añade este método para modificar la consulta base
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereIn('estado', ['MERMA', 'PERDIDO']);
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
            'index' => Pages\ListControlMaletaDetalles::route('/'),
            // 'create' => Pages\CreateControlMaletaDetalle::route('/create'),
            // 'edit' => Pages\EditControlMaletaDetalle::route('/{record}/edit'),
        ];
    }
}
