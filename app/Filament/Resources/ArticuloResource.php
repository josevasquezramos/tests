<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArticuloResource\Pages;
use App\Filament\Resources\ArticuloResource\RelationManagers;
use App\Filament\Resources\ArticuloResource\RelationManagers\AbiertosRelationManager;
use App\Models\Articulo;
use App\Models\ArticuloCategoria;
use App\Models\ArticuloMarca;
use App\Models\ArticuloUnidad;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ArticuloResource extends Resource
{
    protected static ?string $model = Articulo::class;

    protected static ?string $navigationGroup = 'Pruebas de logística';

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        // Sección ARTÍCULO (2 columnas)
                        Forms\Components\Section::make('Artículo')
                            ->columnSpan(2)
                            ->schema([
                                Forms\Components\Select::make('categoria_id')
                                    ->label('Categoría')
                                    ->options(ArticuloCategoria::all()->pluck('nombre', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('nombre')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        return ArticuloCategoria::create($data)->id;
                                    }),

                                Forms\Components\Select::make('marca_id')
                                    ->label('Marca')
                                    ->options(ArticuloMarca::all()->pluck('nombre', 'id'))
                                    ->searchable()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('nombre')
                                            ->required()
                                            ->maxLength(255),
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        return ArticuloMarca::create($data)->id;
                                    }),

                                Forms\Components\TextInput::make('descripcion')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Forms\Components\TextInput::make('descripcion_interna')
                                    ->maxLength(255)
                                    ->columnSpanFull(),

                                Forms\Components\Grid::make()
                                    ->schema([

                                        Forms\Components\TextInput::make('contenido')
                                            ->numeric()
                                            ->step(0.01)
                                            // ->hidden(fn(Forms\Get $get): bool => !$get('fraccionable'))
                                            ->dehydrated(fn(?string $state): bool => filled($state))
                                            ->required(fn(Forms\Get $get): bool => $get('fraccionable'))
                                            ->disabled(fn(Forms\Get $get): bool => !$get('fraccionable')),

                                        Forms\Components\Select::make('unidad_id')
                                            ->label('Unidad')
                                            ->options(ArticuloUnidad::all()->pluck('nombre', 'id'))
                                            ->searchable()
                                            ->default(1)
                                            // ->disabled(fn(Forms\Get $get): bool => !$get('fraccionable'))
                                            // ->hidden(fn(Forms\Get $get): bool => !$get('fraccionable'))
                                            ->createOptionForm([
                                                Forms\Components\TextInput::make('abreviatura')
                                                    ->required()
                                                    ->maxLength(10),
                                                Forms\Components\TextInput::make('nombre')
                                                    ->required()
                                                    ->maxLength(255),
                                            ])
                                            ->createOptionUsing(function (array $data) {
                                                return ArticuloUnidad::create($data)->id;
                                            }),
                                    ]),

                                Forms\Components\TextInput::make('costo')
                                    ->numeric()
                                    ->prefix('S/')
                                    ->step(0.01)
                                    ->required(),

                                Forms\Components\TextInput::make('precio')
                                    ->numeric()
                                    ->prefix('S/')
                                    ->step(0.01)
                                    ->required(),
                            ])
                            ->columns(2),

                        // Sección INVENTARIO (1 columna)
                        Forms\Components\Section::make('Inventario')
                            ->columnSpan(1)
                            ->schema([
                                Forms\Components\TextInput::make('stock')
                                    ->numeric()
                                    ->step(0.01)
                                    ->default(0)
                                    ->required(),

                                Forms\Components\Toggle::make('fraccionable')
                                    ->live()
                                    ->onColor('success')
                                    ->offColor('danger')
                                    ->afterStateUpdated(function (Forms\Set $set, ?bool $state) {
                                        if (!$state) {
                                            $set('contenido', null);
                                            $set('unidad_id', 1); // Asume que la unidad con ID 1 es la por defecto
                                        }
                                    }),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ColumnGroup::make('Artículo', [
                    Tables\Columns\TextColumn::make('categoria.nombre')
                        ->sortable(),
                    Tables\Columns\TextColumn::make('marca.nombre')
                        ->sortable(),
                    Tables\Columns\TextColumn::make('descripcion')
                        ->searchable(),
                ]),
                Tables\Columns\ColumnGroup::make('Inventario', [
                    Tables\Columns\TextColumn::make('stock')
                        ->alignCenter()
                        ->numeric()
                        ->sortable(),
                    Tables\Columns\TextColumn::make('abiertos_count')
                        ->alignCenter()
                        ->label('Abiertos')
                        ->sortable(),
                    Tables\Columns\TextColumn::make('contenido')
                        ->alignEnd()
                        ->placeholder('N/A'),
                    Tables\Columns\TextColumn::make('unidad.abreviatura')
                        ->alignStart()
                        ->placeholder('N/A'),
                    Tables\Columns\IconColumn::make('fraccionable')
                        ->alignCenter()
                        ->label('Frac.')
                        ->boolean(),
                ]),
                Tables\Columns\ColumnGroup::make('Precio', [
                    Tables\Columns\TextColumn::make('costo')
                        ->label('Compra')
                        ->sortable()
                        ->prefix('S/ '),
                    Tables\Columns\TextColumn::make('precio')
                        ->label('Venta')
                        ->sortable()
                        ->prefix('S/ '),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->filters([
                //
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

    public static function getRelations(): array
    {
        // Obtener el ID del registro actual (si existe)
        $recordId = request()->route('record');

        if ($recordId) {
            $articulo = Articulo::find($recordId);

            // Solo devolver el RelationManager si el artículo es fraccionable
            if ($articulo && $articulo->fraccionable) {
                return [
                    RelationManagers\AbiertosRelationManager::class,
                ];
            }
        }

        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArticulos::route('/'),
            'create' => Pages\CreateArticulo::route('/create'),
            'edit' => Pages\EditArticulo::route('/{record}/edit'),
        ];
    }
}
