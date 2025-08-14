<?php

namespace App\Filament\Resources\TrabajoResource\Pages;

use App\Filament\Resources\TrabajoResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTrabajo extends CreateRecord
{
    protected static string $resource = TrabajoResource::class;

    /** @var array<int> */
    protected array $documentoIds = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Extraer y guardar los IDs seleccionados desde el repeater
        $this->documentoIds = collect($data['documentos'] ?? [])
            ->pluck('documento_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        // Quitar 'documentos' del payload para evitar inserciones erróneas
        unset($data['documentos']);

        return $data;
    }

    protected function afterCreate(): void
    {
        // Sincronizar relación M:N
        $this->record->documentos()->sync($this->documentoIds);
    }
}
