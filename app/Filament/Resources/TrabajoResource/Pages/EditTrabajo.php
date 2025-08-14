<?php

namespace App\Filament\Resources\TrabajoResource\Pages;

use App\Filament\Resources\TrabajoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTrabajo extends EditRecord
{
    protected static string $resource = TrabajoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
