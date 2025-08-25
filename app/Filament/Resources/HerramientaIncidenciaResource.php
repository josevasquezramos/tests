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

            // Maleta (CREATE: select) ------------------------
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
                        $set('propietario_id', $maleta?->propietario_id);
                        $set('propietario_nombre', $maleta?->propietario?->name ?? 'No asignado');
                    } else {
                        $set('propietario_id', null);
                        $set('propietario_nombre', null);
                    }
                    $set('maleta_detalle_id', null); // limpiar herramienta al cambiar maleta
                })
                ->hiddenOn('edit'),

            // Maleta (EDIT: texto readonly) ------------------
            TextInput::make('maleta_codigo')
                ->label('Maleta')
                ->readOnly()
                ->dehydrated(false)
                ->visibleOn('edit'),

            // Propietario ------------------------------------
            TextInput::make('propietario_nombre')
                ->label('Propietario')
                ->readOnly()
                ->dehydrated(false)
                ->placeholder('No asignado')
                ->disabled(
                    fn(Get $get) =>
                    // sin propietario_id => deshabilitado
                    !$get('propietario_id')
                    // o si por UI trae estos textos
                    || in_array(strtolower((string) $get('propietario_nombre')), ['no asignado', 'sin asignar'])
                ),
            Hidden::make('propietario_id'),

            // Herramienta (CREATE: select de maleta_detalles activos) ------------
            Select::make('maleta_detalle_id')
                ->label('Herramienta')
                ->options(function (Get $get) {
                    $maletaId = $get('maleta_id');
                    if (!$maletaId)
                        return [];
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
                        ]);
                })
                ->required()
                ->reactive()
                ->searchable()
                ->hiddenOn('edit'),

            // Herramienta (EDIT: texto readonly + hidden con el id) --------------
            TextInput::make('herramienta_nombre')
                ->label('Herramienta')
                ->readOnly()
                ->dehydrated(false)
                ->visibleOn('edit'),
            Hidden::make('maleta_detalle_id')
                ->visibleOn('edit'), // se envía el id real intacto en edición

            // Motivo --------------------------------------------------------------
            Select::make('motivo')
                ->label('Motivo')
                ->options([
                    'MERMA' => 'MERMA',
                    'PERDIDO' => 'PERDIDO',
                ])
                ->native(false)
                ->required(),

            // Observación ---------------------------------------------------------
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

                TextColumn::make('herramienta_nombre')
                    ->label('Herramienta')
                    ->getStateUsing(function ($record) {
                        $md = $record->maletaDetalle()
                            ->withTrashed()        // incluir soft-deleted
                            ->with('herramienta')
                            ->first();

                        return $md?->herramienta?->nombre
                            ?? "Detalle #{$record->maleta_detalle_id}";
                    })
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
                // opcional: filtros por motivo, sin propietario, etc.
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                // Descomenta si quieres bulk delete:
                // Tables\Actions\BulkActionGroup::make([
                //     Tables\Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'propietario',
            'responsable',
            // 👇 Incluye maletaDetalle aunque esté soft-deleted, y su herramienta
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
