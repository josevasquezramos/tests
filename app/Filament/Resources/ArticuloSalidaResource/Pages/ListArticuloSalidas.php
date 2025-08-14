<?php

namespace App\Filament\Resources\ArticuloSalidaResource\Pages;

use App\Filament\Resources\ArticuloSalidaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListArticuloSalidas extends ListRecords
{
    protected static string $resource = ArticuloSalidaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
