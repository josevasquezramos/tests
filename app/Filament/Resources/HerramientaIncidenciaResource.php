<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HerramientaIncidenciaResource\Pages;
use App\Models\HerramientaIncidencia;
use App\Models\Herramienta;
use App\Models\Maleta;
use App\Models\MaletaDetalle;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class HerramientaIncidenciaResource extends Resource
{
    protected static ?string $model = HerramientaIncidencia::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';
    protected static ?string $navigationGroup = 'Pruebas de herramientas';
    protected static ?int $navigationSort = 150;

    public static function form(Form $form): Form
    {
        return $form->schema([
            DateTimePicker::make('fecha')
                ->label('Fecha')
                ->seconds(false)
                ->default(now())
                ->required(),

            // Solo UI - Responsable
            TextInput::make('responsable_nombre')
                ->label('Responsable')
                ->default(fn() => Auth::user()?->name)
                ->readOnly()
                ->dehydrated(false),

            // --- SELECTOR DE TIPO DE ORIGEN (CREATE) ---
            Select::make('tipo_origen')
                ->label('Origen de la incidencia')
                ->options([
                    'MALETA' => 'Desde Maleta',
                    'STOCK' => 'Desde Stock/Almacén',
                ])
                ->native(false)
                ->required()
                ->reactive()
                ->afterStateUpdated(function ($state, Set $set) {
                    // Limpiar campos al cambiar tipo
                    $set('maleta_id', null);
                    $set('maleta_detalle_id', null);
                    $set('herramienta_id', null);
                    $set('cantidad', 1);
                    $set('propietario_id', null);
                    $set('propietario_nombre', null);
                })
                ->hiddenOn('edit'),

            // --- TIPO DE ORIGEN (EDIT - Solo lectura) ---
            TextInput::make('tipo_origen_display')
                ->label('Tipo de incidencia')
                ->readOnly()
                ->dehydrated(false)
                ->afterStateHydrated(function (TextInput $component, $record) {
                    if ($record) {
                        $component->state($record->tipo_origen === 'MALETA' ? 'Desde Maleta' : 'Desde Stock/Almacén');
                    }
                })
                ->visibleOn('edit'),

            // ========== SECCIÓN PARA TIPO MALETA ==========

            // --- MALETA (CREATE) ---
            Select::make('maleta_id')
                ->label('Maleta')
                ->options(fn() => Maleta::query()
                    ->orderBy('codigo')
                    ->pluck('codigo', 'id'))
                ->searchable()
                ->preload()
                ->reactive()
                ->dehydrated(false)
                ->visible(fn(Get $get) => $get('tipo_origen') === 'MALETA')
                ->required(fn(Get $get) => $get('tipo_origen') === 'MALETA')
                ->afterStateUpdated(function ($state, Set $set) {
                    if ($state) {
                        $maleta = Maleta::with('propietario')->find($state);

                        // Propietario según maleta elegida
                        if ($maleta?->propietario_id) {
                            $set('propietario_id', $maleta->propietario_id);
                            $set('propietario_nombre', $maleta->propietario?->name);
                        } else {
                            $set('propietario_id', null);
                            $set('propietario_nombre', 'No asignado');
                        }
                    } else {
                        $set('propietario_id', null);
                        $set('propietario_nombre', null);
                    }

                    // Limpiar herramienta al cambiar maleta
                    $set('maleta_detalle_id', null);
                })
                ->hiddenOn('edit'),

            // --- MALETA (EDIT) ---
            TextInput::make('maleta_codigo')
                ->label('Maleta')
                ->readOnly()
                ->dehydrated(false)
                ->visible(
                    fn($record, $operation) =>
                    $operation === 'edit' &&
                    $record &&
                    $record->tipo_origen === 'MALETA'
                ),

            // --- PROPIETARIO (UI) ---
            TextInput::make('propietario_nombre')
                ->label('Propietario')
                ->readOnly()
                ->dehydrated(false)
                ->visible(
                    fn(Get $get, $record, $operation) =>
                    ($operation === 'create' && $get('tipo_origen') === 'MALETA') ||
                    ($operation === 'edit' && $record && $record->tipo_origen === 'MALETA')
                )
                ->afterStateHydrated(function (TextInput $component, $state, $record) {
                    if ($record && $record->tipo_origen === 'MALETA' && blank($record->propietario_id)) {
                        $component->state('No asignado');
                    } elseif ($record && $record->tipo_origen === 'STOCK') {
                        $component->state('N/A');
                    }
                }),
            Hidden::make('propietario_id'),

            // --- HERRAMIENTA DESDE MALETA (CREATE) ---
            Select::make('maleta_detalle_id')
                ->label('Herramienta')
                ->options(function (Get $get) {
                    $maletaId = $get('maleta_id');
                    if (!$maletaId) {
                        return [];
                    }

                    return MaletaDetalle::query()
                        ->with('herramienta')
                        ->where('maleta_id', $maletaId)
                        ->whereNull('deleted_at')
                        ->orderByDesc('id')
                        ->get()
                        ->mapWithKeys(fn($md) => [
                            $md->id => $md->herramienta?->nombre
                                ? $md->herramienta->nombre
                                : "Detalle #{$md->id}",
                        ])
                        ->toArray();
                })
                ->reactive()
                ->searchable()
                ->disabled(fn(Get $get) => blank($get('maleta_id')))
                ->required(fn(Get $get) => $get('tipo_origen') === 'MALETA' && filled($get('maleta_id')))
                ->dehydrated(fn(Get $get) => $get('tipo_origen') === 'MALETA')
                ->visible(fn(Get $get) => $get('tipo_origen') === 'MALETA')
                ->hiddenOn('edit'),

            // ========== SECCIÓN PARA TIPO STOCK ==========

            // --- HERRAMIENTA DESDE STOCK (CREATE) ---
            Select::make('herramienta_id')
                ->label('Herramienta')
                ->options(function () {
                    return Herramienta::query()
                        ->where('stock', '>', 0)
                        ->orderBy('nombre')
                        ->get()
                        ->mapWithKeys(fn($h) => [
                            $h->id => "{$h->nombre} (Stock: {$h->stock})"
                        ])
                        ->toArray();
                })
                ->searchable()
                ->preload()
                ->required(fn(Get $get) => $get('tipo_origen') === 'STOCK')
                ->dehydrated(fn(Get $get) => $get('tipo_origen') === 'STOCK')
                ->visible(fn(Get $get) => $get('tipo_origen') === 'STOCK')
                ->reactive()
                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                    if ($state && $get('tipo_origen') === 'STOCK') {
                        $herramienta = Herramienta::find($state);
                        $set('max_cantidad', $herramienta?->stock ?? 0);
                    }
                })
                ->hiddenOn('edit'),

            // --- CANTIDAD (SOLO PARA STOCK) ---
            TextInput::make('cantidad')
                ->label('Cantidad')
                ->numeric()
                ->minValue(1)
                ->maxValue(fn(Get $get) => $get('max_cantidad') ?? 999999)
                ->default(1)
                ->required(fn(Get $get) => $get('tipo_origen') === 'STOCK')
                ->dehydrated(fn(Get $get) => $get('tipo_origen') === 'STOCK')
                ->visible(fn(Get $get) => $get('tipo_origen') === 'STOCK')
                ->helperText(
                    fn(Get $get) =>
                    $get('max_cantidad')
                    ? "Máximo disponible: {$get('max_cantidad')}"
                    : null
                )
                ->hiddenOn('edit'),

            Hidden::make('max_cantidad')->dehydrated(false),

            // --- HERRAMIENTA (EDIT - para ambos tipos) ---
            TextInput::make('herramienta_nombre')
                ->label('Herramienta')
                ->readOnly()
                ->dehydrated(false)
                ->visibleOn('edit'),

            // --- CANTIDAD (EDIT - solo mostrar si es STOCK) ---
            TextInput::make('cantidad_display')
                ->label('Cantidad')
                ->readOnly()
                ->dehydrated(false)
                ->visible(
                    fn($record, $operation) =>
                    $operation === 'edit' &&
                    $record &&
                    $record->tipo_origen === 'STOCK'
                )
                ->afterStateHydrated(function (TextInput $component, $record) {
                    if ($record && $record->tipo_origen === 'STOCK') {
                        $component->state($record->cantidad);
                    }
                }),

            // Hidden para maleta_detalle_id en edit
            Hidden::make('maleta_detalle_id')
                ->visibleOn('edit'),

            // --- MOTIVO ---
            Select::make('motivo')
                ->label('Motivo')
                ->options([
                    'MERMA' => 'MERMA',
                    'PERDIDO' => 'PERDIDO',
                ])
                ->native(false)
                ->required()
                ->hiddenOn('edit') // Ocultar en edición
                ->dehydrated(true),

            // --- MOTIVO (EDIT - readonly) ---
            TextInput::make('motivo_display')
                ->label('Motivo')
                ->readOnly()
                ->dehydrated(false)
                ->visibleOn('edit')
                ->afterStateHydrated(function (TextInput $component, $record) {
                    if ($record) {
                        $component->state($record->motivo);
                    }
                }),

            // --- OBSERVACIÓN ---
            Textarea::make('observacion')
                ->label('Observación')
                ->rows(3)
                ->maxLength(1000),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fecha')
                    ->label('Fecha')
                    ->dateTime('d/m/Y h:i A')
                    ->sortable(),

                TextColumn::make('tipo_origen')
                    ->label('Origen')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'MALETA' => 'info',
                        'STOCK' => 'success',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'MALETA' => 'Maleta',
                        'STOCK' => 'Stock',
                    })
                    ->sortable(),

                TextColumn::make('herramienta_display')
                    ->label('Herramienta')
                    ->wrap()
                    ->getStateUsing(function ($record) {
                        if ($record->tipo_origen === 'MALETA') {
                            $md = $record->maletaDetalle()
                                ->withTrashed()
                                ->with('herramienta')
                                ->first();
                            return $md?->herramienta?->nombre
                                ?? "Detalle #{$record->maleta_detalle_id}";
                        } else {
                            return $record->herramienta?->nombre
                                ?? "Herramienta #{$record->herramienta_id}";
                        }
                    })
                    ->searchable(
                        query: function (Builder $query, string $search) {
                            $query->where(function ($q) use ($search) {
                                // Buscar en maleta_detalles
                                $q->whereHas('maletaDetalle', function ($q2) use ($search) {
                                    $q2->withTrashed()
                                        ->whereHas('herramienta', function ($q3) use ($search) {
                                            $q3->where('nombre', 'like', "%{$search}%");
                                        });
                                })
                                    // O buscar directo en herramientas
                                    ->orWhereHas('herramienta', function ($q2) use ($search) {
                                    $q2->where('nombre', 'like', "%{$search}%");
                                });
                            });
                        },
                        isIndividual: true
                    ),

                TextColumn::make('cantidad')
                    ->label('Cantidad')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('propietario.name')
                    ->label('Propietario')
                    ->placeholder('No corresponde')
                    ->sortable()
                    ->searchable(isIndividual: true),

                TextColumn::make('motivo')
                    ->label('Motivo')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'MERMA' => 'warning',
                        'PERDIDO' => 'danger',
                    })
                    ->sortable(),

                TextColumn::make('responsable.name')
                    ->label('Responsable')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->searchable(isIndividual: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo_origen')
                    ->label('Tipo')
                    ->options([
                        'MALETA' => 'Desde Maleta',
                        'STOCK' => 'Desde Stock',
                    ]),
                Tables\Filters\SelectFilter::make('motivo')
                    ->options([
                        'MERMA' => 'MERMA',
                        'PERDIDO' => 'PERDIDO',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'propietario',
            'responsable',
            'herramienta',
            'maletaDetalle' => fn($q) => $q->withTrashed()->with(['herramienta', 'maleta']),
        ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHerramientaIncidencias::route('/'),
            'create' => Pages\CreateHerramientaIncidencia::route('/create'),
            'edit' => Pages\EditHerramientaIncidencia::route('/{record}/edit'),
        ];
    }
}