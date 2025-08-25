<?php

namespace App\Filament\Resources\ControlMaletaDetalleResource\Pages;

use App\Filament\Resources\ControlMaletaDetalleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditControlMaletaDetalle extends EditRecord
{
    protected static string $resource = ControlMaletaDetalleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
