<?php

namespace App\Filament\Resources\MaletaResource\Pages;

use App\Filament\Resources\MaletaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMaleta extends EditRecord
{
    protected static string $resource = MaletaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
