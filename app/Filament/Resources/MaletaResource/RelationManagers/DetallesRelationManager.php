<?php

namespace App\Filament\Resources\MaletaResource\RelationManagers;

use App\Models\Herramienta;
use Filament\Forms;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;

class DetallesRelationManager extends RelationManager
{
    protected static string $relationship = 'detalles';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('herramienta_id')
                    ->label('Herramienta')
                    ->required()
                    ->relationship('herramienta', 'nombre')
                    ->searchable()
                    ->disabledOn('edit')
                    ->preload()
                    ->live()
                    ->rule(fn() => function (string $attribute, $value, \Closure $fail) {
                        if (!$value)
                            return;
                        $stock = Herramienta::query()->whereKey($value)->value('stock');
                        if ($stock !== null && $stock <= 0) {
                            $fail('Stock insuficiente.');
                        }
                    }),
                Placeholder::make('stock_info')
                    ->label('Stock disponible')
                    ->content(function (Get $get) {
                        $id = $get('herramienta_id');
                        if (!$id)
                            return new HtmlString('<span class="fi-ta-placeholder text-sm leading-6 text-gray-400 dark:text-gray-500">Seleccione una herramienta</span>');
                        $stock = Herramienta::query()->whereKey($id)->value('stock');
                        return $stock === null ? '—' : (string) $stock;
                    })
                    ->visibleOn('create'),
                TextInput::make('ultimo_estado')
                    ->disabled()
                    ->hiddenOn('create'),
                FileUpload::make('evidencia_url')
                    ->directory('maleta-detalles'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('herramienta.nombre')
                    ->searchable(isIndividual: true)
                    ->sortable(),
                ImageColumn::make('evidencia_url')
                    ->width(70)
                    ->height(70),
                TextColumn::make('ultimo_estado')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'OPERATIVO' => 'success',
                        'MERMA' => 'warning',
                        'PERDIDO' => 'danger',
                    })
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make()
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
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
