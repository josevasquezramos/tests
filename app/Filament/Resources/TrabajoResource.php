<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrabajoResource\Pages;
use App\Models\Documento;
use App\Models\Trabajo;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Storage;

class TrabajoResource extends Resource
{
    protected static ?string $model = Trabajo::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                
                Forms\Components\TextInput::make('importe')
                    ->required()
                    ->numeric(),
                
                Forms\Components\Repeater::make('documentos')
                    ->relationship('documentos')
                    ->schema([
                        Forms\Components\Select::make('id')
                            ->label('Documento')
                            ->options(Documento::query()->pluck('nombre', 'id'))
                            ->searchable()
                            ->required()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('nombre')
                                    ->required(),
                                Forms\Components\TextInput::make('importe')
                                    ->required()
                                    ->numeric(),
                                Forms\Components\FileUpload::make('url')
                                    ->required()
                                    ->directory('documentos'),
                            ])
                            ->createOptionUsing(function (array $data) {
                                try {
                                    DB::beginTransaction();
                                    
                                    $documento = Documento::create([
                                        'nombre' => $data['nombre'],
                                        'importe' => $data['importe'],
                                        'url' => $data['url'],
                                    ]);
                                    
                                    DB::commit();
                                    
                                    return $documento->id;
                                } catch (\Exception $e) {
                                    DB::rollBack();
                                    throw $e;
                                }
                            })
                            ->editOptionForm([
                                Forms\Components\TextInput::make('nombre')
                                    ->required(),
                                Forms\Components\TextInput::make('importe')
                                    ->required()
                                    ->numeric(),
                                Forms\Components\FileUpload::make('url')
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
                                try {
                                    DB::beginTransaction();
                                    
                                    $documento = Documento::find($state);
                                    $documento->update([
                                        'nombre' => $data['nombre'],
                                        'importe' => $data['importe'],
                                        'url' => $data['url'] ?? $documento->url,
                                    ]);
                                    
                                    DB::commit();
                                } catch (\Exception $e) {
                                    DB::rollBack();
                                    throw $e;
                                }
                            })
                            ->distinct()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                    ])
                    ->defaultItems(1)
                    ->columnSpanFull()
                    ->addActionLabel('Agregar Documento')
                    ->reorderable(false),
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
                Tables\Columns\ViewColumn::make('documentos')
                    ->label('Documentos')
                    ->view('filament.tables.columns.documentos-badges'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
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