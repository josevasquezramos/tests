<?php

namespace App\Filament\Resources\TrabajoResource\Pages;

use App\Filament\Resources\TrabajoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTrabajo extends EditRecord
{
    protected static string $resource = TrabajoResource::class;

    /** @var array<int> */
    protected array $documentoIds = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Extraer y guardar los IDs del repeater
        $this->documentoIds = collect($data['documentos'] ?? [])
            ->pluck('documento_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        // Quitar 'documentos' para no intentar persistirlos como atributos del modelo
        unset($data['documentos']);

        return $data;
    }

    protected function afterSave(): void
    {
        // Sincronizar la relación al guardar cambios
        $this->record->documentos()->sync($this->documentoIds);
    }
}
