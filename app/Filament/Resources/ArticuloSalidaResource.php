<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArticuloSalidaResource\Pages;
use App\Filament\Resources\ArticuloSalidaResource\RelationManagers;
use App\Models\Articulo;
use App\Models\ArticuloAbierto;
use App\Models\ArticuloSalida;
use App\Models\ArticuloUnidad;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ArticuloSalidaResource extends Resource
{
    protected static ?string $model = ArticuloSalida::class;

    protected static ?string $navigationGroup = 'Pruebas de logística';

    protected static ?string $navigationIcon = 'heroicon-o-inbox';

    protected static ?string $modelLabel = 'Salida';

    protected static ?string $pluralModelLabel = 'Salidas';

    protected static ?string $slug = 'salidas';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        // Sección SALIDA (2 columnas)
                        Forms\Components\Section::make('Salida')
                            ->columnSpan(2)
                            ->schema([
                                Forms\Components\Select::make('articulo_id')
                                    ->label('Artículo')
                                    ->columnSpanFull()
                                    ->options(function () {
                                        return Articulo::with(['categoria', 'marca', 'unidad'])
                                            ->get()
                                            ->mapWithKeys(function ($item) {
                                                $baseText = sprintf(
                                                    '%s %s %s %s',
                                                    $item->categoria->nombre,
                                                    $item->marca->nombre,
                                                    $item->descripcion,
                                                    $item?->descripcion_interna
                                                );

                                                if ($item->fraccionable) {
                                                    $baseText .= sprintf(
                                                        ' (%.2f %s)',
                                                        $item->contenido,
                                                        $item->unidad->abreviatura
                                                    );
                                                }

                                                return [$item->id => $baseText];
                                            });
                                    })
                                    ->searchable(['descripcion', 'categoria.nombre', 'marca.nombre'])
                                    ->required()
                                    ->live()
                                    ->disabled(fn(string $operation): bool => $operation === 'edit')
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        if ($state) {
                                            $articulo = Articulo::find($state);
                                            $set('unidad_id', $articulo->unidad_id);
                                            $set('stock_actual', $articulo->stock);
                                            $set('articulo_abierto_id', null);

                                            // Actualizar el consumible con el contenido del artículo
                                            $set('consumible', $articulo->contenido);
                                        } else {
                                            $set('unidad_id', null);
                                            $set('stock_actual', null);
                                            $set('consumible', null);
                                        }
                                    }),

                                Forms\Components\TextInput::make('cantidad_enteros')
                                    ->label('Cantidad de unidades enteras')
                                    ->numeric()
                                    ->integer()
                                    ->minValue(1)
                                    ->required()
                                    ->hidden(fn(Forms\Get $get): bool =>
                                        $get('modo_salida') !== 'enteros' || !static::isArticuloFraccionable($get('articulo_id')))
                                    ->live(),

                                Forms\Components\TextInput::make('consumible')
                                    ->label('Contenido consumible')
                                    ->numeric()
                                    ->disabled()
                                    ->suffix(function (Forms\Get $get) {
                                        $unidadId = $get('unidad_id');
                                        if (!$unidadId)
                                            return '';

                                        $unidad = ArticuloUnidad::find($unidadId);
                                        return $unidad ? $unidad->abreviatura : '';
                                    })
                                    ->default(0)
                                    ->hidden(fn(Forms\Get $get): bool =>
                                        !static::isArticuloFraccionable($get('articulo_id')) || $get('modo_salida') === 'enteros')
                                    ->dehydrated()
                                    ->afterStateHydrated(function (Forms\Components\TextInput $component, Forms\Get $get) {
                                        $articuloId = $get('articulo_id');
                                        $abiertoId = $get('articulo_abierto_id');
                                        $originalCantidad = $get('_original_record.cantidad') ?? 0; // Obtener cantidad original
                            
                                        if (!$articuloId) {
                                            $component->state(0);
                                            return;
                                        }

                                        $articulo = Articulo::find($articuloId);

                                        if ($abiertoId) {
                                            $abierto = ArticuloAbierto::find($abiertoId);
                                            $restante = $abierto ? $abierto->restante : 0;
                                            // Sumar la cantidad original al restante durante la edición
                                            $component->state($originalCantidad ? $restante + $originalCantidad : $restante);
                                        } else {
                                            $component->state($articulo ? $articulo->contenido : 0);
                                        }
                                    }),

                                Forms\Components\TextInput::make('cantidad')
                                    ->hidden(fn(Forms\Get $get): bool => $get('modo_salida') === 'enteros')
                                    ->numeric()
                                    ->step(0.01)
                                    ->required()
                                    ->suffix(function (Forms\Get $get) {
                                        $unidadId = $get('unidad_id');
                                        if (!$unidadId)
                                            return '';

                                        $unidad = ArticuloUnidad::find($unidadId);
                                        return $unidad ? $unidad->abreviatura : '';
                                    })
                                    ->live(),
                            ])
                            ->columns(2),

                        // Sección DETALLES (1 columna)
                        Forms\Components\Section::make('Detalles')
                            ->columnSpan(1)
                            ->schema([

                                Forms\Components\Select::make('modo_salida')
                                    ->label('Modo de salida')
                                    ->options([
                                        'normal' => 'Normal',
                                        'enteros' => 'Enteros (Especial)'
                                    ])
                                    ->default('normal')
                                    ->live()
                                    ->hidden(function (string $operation, Forms\Get $get): bool {
                                        // Ocultar siempre en edición O cuando no es artículo fraccionable
                                        return $operation === 'edit' || !static::isArticuloFraccionable($get('articulo_id'));
                                    })
                                    ->afterStateUpdated(function ($state, Forms\Set $set) {
                                        $set('cantidad_enteros', null);
                                    }),

                                Forms\Components\TextInput::make('stock_actual')
                                    ->label('Stock disponible')
                                    ->numeric()
                                    ->disabled(),

                                Forms\Components\Select::make('articulo_abierto_id')
                                    ->label('Unidad abierta')
                                    ->options(function (Forms\Get $get, $record = null) {
                                        $articuloId = $get('articulo_id');
                                        if (!$articuloId) {
                                            return [];
                                        }

                                        if ($record?->articulo_abierto_id) {
                                            $query = ArticuloAbierto::withTrashed()->where('articulo_id', $articuloId);
                                        } else {
                                            $query = ArticuloAbierto::where('articulo_id', $articuloId);
                                        }

                                        return $query
                                            ->get()
                                            ->mapWithKeys(function ($item) {
                                                $deletedText = $item->trashed() ? '' : '';
                                                return [
                                                    $item->id => sprintf(
                                                        'Abierto %d - %s (%.2f %s)%s',
                                                        $item->id,
                                                        $item->created_at->format('d/m/Y'),
                                                        $item->restante,
                                                        $item->articulo->unidad->abreviatura,
                                                        $deletedText
                                                    )
                                                ];
                                            });
                                    })
                                    ->searchable()
                                    ->hidden(fn(Forms\Get $get): bool =>
                                        !static::isArticuloFraccionable($get('articulo_id')) || $get('modo_salida') === 'enteros')
                                    ->disabled(fn(string $operation): bool => $operation === 'edit')
                                    ->live()
                                    ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                        $articuloId = $get('articulo_id');
                                        if (!$articuloId)
                                            return;

                                        $articulo = Articulo::find($articuloId);

                                        if ($state) {
                                            $abierto = ArticuloAbierto::find($state);
                                            $set('consumible', $abierto ? $abierto->restante : $articulo->contenido);
                                        } else {
                                            $set('consumible', $articulo->contenido);
                                        }
                                    }),
                            ]),
                    ]),
            ]);
    }

    protected static function isArticuloFraccionable(?int $articuloId): bool
    {
        if (!$articuloId)
            return false;

        $articulo = Articulo::find($articuloId);
        return $articulo ? $articulo->fraccionable : false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('articulo.descripcion')
                    ->formatStateUsing(function ($record) {
                        $categoria = $record->articulo->categoria->nombre;
                        $marca = $record->articulo->marca->nombre;
                        $descripcion = $record->articulo->descripcion;
                        return "$categoria $marca $descripcion";
                    })
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cantidad')
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unidad.abreviatura')
                    ->alignCenter()
                    ->label('U.M.'),
                Tables\Columns\TextColumn::make('precio')
                    ->alignEnd()
                    ->label('P.U.')
                    ->prefix('S/ ')
                    ->sortable(),
                Tables\Columns\TextColumn::make('Total')
                    ->alignEnd()
                    ->state(
                        fn($record) =>
                        number_format($record->precio * $record->cantidad, 2)
                    )
                    ->prefix('S/ ')
                    ->sortable(query: function (Builder $query, string $direction) {
                        $query->orderByRaw("(precio * cantidad) $direction");
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
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
            'index' => Pages\ListArticuloSalidas::route('/'),
            'create' => Pages\CreateArticuloSalida::route('/create'),
            'edit' => Pages\EditArticuloSalida::route('/{record}/edit'),
        ];
    }
}
