<?php

namespace App\Filament\Resources\TrabajoResource\Pages;

use App\Filament\Resources\TrabajoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTrabajos extends ListRecords
{
    protected static string $resource = TrabajoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
