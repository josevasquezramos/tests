<?php

namespace App\Filament\Resources\HerramientaIncidenciaResource\Pages;

use App\Filament\Resources\HerramientaIncidenciaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHerramientaIncidencias extends ListRecords
{
    protected static string $resource = HerramientaIncidenciaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
