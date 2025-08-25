<?php

namespace App\Filament\Resources\ControlMaletaDetalleResource\Pages;

use App\Filament\Resources\ControlMaletaDetalleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListControlMaletaDetalles extends ListRecords
{
    protected static string $resource = ControlMaletaDetalleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
