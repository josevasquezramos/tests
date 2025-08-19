<?php

namespace App\Filament\Resources\ControlMaletaResource\Pages;

use App\Filament\Resources\ControlMaletaResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateControlMaleta extends CreateRecord
{
    protected static string $resource = ControlMaletaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['responsable_id'] = auth()->id();
        return $data;
    }
}
