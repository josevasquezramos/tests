<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HerramientaIncidenciaResource\Pages;
use App\Models\HerramientaIncidencia;
use App\Models\Maleta;
use App\Models\MaletaDetalle;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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

            // Solo UI
            TextInput::make('responsable_nombre')
                ->label('Responsable')
                ->default(fn() => Auth::user()?->name)
                ->readOnly()
                ->dehydrated(false),

            // --- MALETA (CREATE) ---
            Select::make('maleta_id')
                ->label('Maleta')
                ->options(fn() => Maleta::query()
                    ->orderBy('codigo')
                    ->pluck('codigo', 'id'))
                ->searchable()
                ->preload()
                ->required()
                ->reactive()
                ->dehydrated(false)
                ->afterStateUpdated(function ($state, Set $set) {
                    if ($state) {
                        $maleta = Maleta::with('propietario')->find($state);

                        // Propietario según maleta elegida
                        if ($maleta?->propietario_id) {
                            $set('propietario_id', $maleta->propietario_id);
                            $set('propietario_nombre', $maleta->propietario?->name);
                        } else {
                            $set('propietario_id', null);
                            $set('propietario_nombre', 'No asignado'); // disabled con texto
                        }
                    } else {
                        // Quitaron la selección → sin texto y disabled
                        $set('propietario_id', null);
                        $set('propietario_nombre', null);
                    }

                    // Siempre limpiar herramienta al cambiar/quitar maleta
                    $set('maleta_detalle_id', null);
                })
                ->hiddenOn('edit'),

            // --- MALETA (EDIT) ---
            TextInput::make('maleta_codigo')
                ->label('Maleta')
                ->readOnly()
                ->dehydrated(false)
                ->visibleOn('edit'),

            // --- PROPIETARIO (UI) ---
            TextInput::make('propietario_nombre')
                ->label('Propietario')
                // readOnly cuando HAY propietario; disabled cuando NO hay
                ->readOnly(fn(Get $get) => filled($get('propietario_id')))
                ->disabled(fn(Get $get) => blank($get('propietario_id')))
                ->dehydrated(false)
                ->afterStateHydrated(function (TextInput $component, $state, $record) {
                    // En edición: si NO hay propietario, mostrar "No asignado" (disabled)
                    if ($record && blank($record->propietario_id)) {
                        $component->state('No asignado');
                    }
                }),
            Hidden::make('propietario_id'),

            // --- HERRAMIENTA (CREATE) ---
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
                        ->whereNull('deleted_at') // solo activos
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
                // 🔒 Deshabilitado hasta seleccionar una maleta
                ->disabled(fn(Get $get) => blank($get('maleta_id')))
                // ✅ Solo se requiere y se deshidrata cuando hay maleta
                ->required(fn(Get $get) => filled($get('maleta_id')))
                ->dehydrated(fn(Get $get) => filled($get('maleta_id')))
                ->hiddenOn('edit'),

            // --- HERRAMIENTA (EDIT) ---
            TextInput::make('herramienta_nombre')
                ->label('Herramienta')
                ->readOnly()
                ->dehydrated(false)
                ->visibleOn('edit'),
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
                ->required(),

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

                TextColumn::make('propietario.name')
                    ->label('Propietario')
                    ->placeholder('No asignado')
                    ->sortable()
                    ->searchable(isIndividual: true),

                TextColumn::make('maletaDetalle.herramienta.nombre')
                    ->label('Herramienta')
                    ->wrap()
                    ->getStateUsing(function ($record) {
                        $md = $record->maletaDetalle()
                            ->withTrashed()
                            ->with('herramienta')
                            ->first();

                        return $md?->herramienta?->nombre
                            ?? "Detalle #{$record->maleta_detalle_id}";
                    })
                    ->searchable(
                        query: function (Builder $query, string $search) {
                            $query->whereHas('maletaDetalle', function ($q) use ($search) {
                                $q->withTrashed()
                                    ->whereHas('herramienta', function ($q2) use ($search) {
                                        $q2->where('nombre', 'like', "%{$search}%");
                                    });
                            });
                        },
                        isIndividual: true
                    )
                    ->sortable(false),

                TextColumn::make('motivo')
                    ->label('Motivo')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'MERMA' => 'warning',
                        'PERDIDO' => 'danger',
                    })
                    ->sortable(),
            ])
            ->filters([
                //
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
            // incluir maletaDetalle (soft-deleted) y su herramienta
            'maletaDetalle' => fn($q) => $q->withTrashed()->with('herramienta'),
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
