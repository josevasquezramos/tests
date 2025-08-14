<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrabajoResource\Pages;
use App\Models\Documento;
use App\Models\Trabajo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TrabajoResource extends Resource
{
    protected static ?string $model = Trabajo::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('importe')
                    ->required()
                    ->numeric(),

                // Repeater manual (sin ->relationship())
                Repeater::make('documentos')
                    ->label('Documentos')
                    ->schema([
                        Select::make('documento_id')
                            ->label('Documento')
                            ->options(fn() => Documento::query()->pluck('nombre', 'id'))
                            ->searchable()
                            ->required()
                            ->distinct()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                            ->createOptionForm([
                                TextInput::make('nombre')
                                    ->required(),
                                TextInput::make('importe')
                                    ->required()
                                    ->numeric(),
                                FileUpload::make('url')
                                    ->required()
                                    ->directory('documentos'),
                            ])
                            ->createOptionUsing(function (array $data) {
                                return DB::transaction(function () use ($data) {
                                    $documento = Documento::create([
                                        'nombre' => $data['nombre'],
                                        'importe' => $data['importe'],
                                        'url' => $data['url'],
                                    ]);
                                    return $documento->id;
                                });
                            })
                            ->editOptionForm([
                                TextInput::make('nombre')
                                    ->required(),
                                TextInput::make('importe')
                                    ->required()
                                    ->numeric(),
                                FileUpload::make('url')
                                    ->directory('documentos'),
                            ])
                            ->fillEditOptionActionFormUsing(function (string $state) {
                                if ($state) {
                                    $documento = Documento::find($state);
                                    return $documento ? $documento->toArray() : [];
                                }
                                return [];
                            })
                            ->updateOptionUsing(function (array $data, string $state) {
                                DB::transaction(function () use ($data, $state) {
                                    $documento = Documento::findOrFail($state);
                                    $documento->update([
                                        'nombre' => $data['nombre'],
                                        'importe' => $data['importe'],
                                        'url' => $data['url'] ?? $documento->url,
                                    ]);
                                });
                            }),
                    ])
                    ->defaultItems(0)
                    ->minItems(0)
                    ->reorderable(false)
                    ->columnSpanFull()
                    ->addActionLabel('Agregar Documento')
                    // Al editar un Trabajo, precargar los documentos ya asociados.
                    ->afterStateHydrated(function (Set $set, ?Trabajo $record) {
                        if (!$record || !$record->exists) {
                            return;
                        }
                        $items = $record->documentos()
                            ->pluck('documentos.id')
                            ->map(fn($id) => ['documento_id' => $id])
                            ->values()
                            ->toArray();

                        $set('documentos', $items);
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('importe')
                    ->money()
                    ->sortable(),
                Tables\Columns\ViewColumn::make('documentos_badges')
                    ->label('Documentos')
                    ->disableClick()
                    ->view('filament.tables.columns.documentos-badges')
                    ->state(function ($record) {
                        return $record->documentos->map(function ($d) {
                            $path = $d->url;
                            $href = $path
                                ? (Str::startsWith($path, ['http://', 'https://', '/'])
                                    ? $path
                                    : Storage::disk('public')->url($path))
                                : null;

                            return [
                                'id' => $d->id,
                                'nombre' => $d->nombre,
                                'url' => $href,
                            ];
                        })->values()->all();
                    }),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->with(['documentos:id,nombre,url']);
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
            'index' => Pages\ListTrabajos::route('/'),
            'create' => Pages\CreateTrabajo::route('/create'),
            'edit' => Pages\EditTrabajo::route('/{record}/edit'),
        ];
    }
}
