<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HerramientaEntradaResource\Pages;
use App\Filament\Resources\HerramientaEntradaResource\RelationManagers;
use App\Filament\Resources\HerramientaEntradaResource\RelationManagers\DetallesRelationManager;
use App\Models\HerramientaEntrada;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class HerramientaEntradaResource extends Resource
{
    protected static ?string $model = HerramientaEntrada::class;

    protected static ?string $navigationGroup = 'Pruebas de herramientas';

    protected static ?string $navigationIcon = 'heroicon-o-wrench';

    protected static ?int $navigationSort = 95;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('codigo')
                    ->required()
                    ->unique(ignoreRecord: true),
                DateTimePicker::make('fecha')
                    ->seconds(false)
                    ->default(now())
                    ->required(),
                TextInput::make('responsable_nombre')
                    ->label('Responsable')
                    ->readOnly()
                    ->dehydrated(false)
                    ->default(fn() => Auth::user()?->name),
                Hidden::make('responsable_id')
                    ->default(fn() => Auth::id())
                    ->required(),
                Textarea::make('observacion'),
                FileUpload::make('evidencia_url')
                    ->directory('entrada-herramientas-entrada'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')
                    ->sortable()
                    ->searchable(isIndividual: true),
                TextColumn::make('responsable.name')
                    ->sortable()
                    ->searchable(isIndividual: true),
                TextColumn::make('fecha')
                    ->dateTime('d/m/Y h:i A')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            DetallesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHerramientaEntradas::route('/'),
            'create' => Pages\CreateHerramientaEntrada::route('/create'),
            'edit' => Pages\EditHerramientaEntrada::route('/{record}/edit'),
        ];
    }
}
