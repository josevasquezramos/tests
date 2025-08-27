<?php

namespace App\Filament\Resources\HerramientaEntradaResource\Pages;

use App\Filament\Resources\HerramientaEntradaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHerramientaEntradas extends ListRecords
{
    protected static string $resource = HerramientaEntradaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
