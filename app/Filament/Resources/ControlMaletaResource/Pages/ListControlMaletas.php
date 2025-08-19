<?php

namespace App\Filament\Resources\ControlMaletaResource\Pages;

use App\Filament\Resources\ControlMaletaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListControlMaletas extends ListRecords
{
    protected static string $resource = ControlMaletaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
