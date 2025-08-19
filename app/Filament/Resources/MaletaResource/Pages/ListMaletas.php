<?php

namespace App\Filament\Resources\MaletaResource\Pages;

use App\Filament\Resources\MaletaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMaletas extends ListRecords
{
    protected static string $resource = MaletaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
