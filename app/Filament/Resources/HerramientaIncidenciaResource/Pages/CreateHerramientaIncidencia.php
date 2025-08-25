<?php

namespace App\Filament\Resources\HerramientaIncidenciaResource\Pages;

use App\Filament\Resources\HerramientaIncidenciaResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateHerramientaIncidencia extends CreateRecord
{
    protected static string $resource = HerramientaIncidenciaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Responsable = usuario actual (no confiamos en campos de UI)
        $data['responsable_id'] = auth()->id();

        // Propietario ya se setea en el form (hidden propietario_id)
        // No tocamos stock ni lógicas de negocio: eso queda en la BD (triggers, etc.)

        return $data;
    }
}
