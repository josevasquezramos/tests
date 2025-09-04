<?php

namespace App\Filament\Resources\HerramientaIncidenciaResource\Pages;

use App\Filament\Resources\HerramientaIncidenciaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHerramientaIncidencia extends EditRecord
{
    protected static string $resource = HerramientaIncidenciaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->record;

        // Responsable y propietario (solo UI)
        $data['responsable_nombre'] = $record->responsable?->name;
        $data['propietario_nombre'] = $record->propietario?->name ?? 'No asignado';

        // Maleta y Herramienta para los campos readonly
        $md = $record->maletaDetalle()->withTrashed()->with(['maleta', 'herramienta'])->first();

        $data['maleta_codigo'] = $md?->maleta?->codigo ?? "Maleta #{$md?->maleta_id}";
        $data['herramienta_nombre'] = $md?->herramienta?->nombre
            ?? "Detalle #{$record->maleta_detalle_id}";

        // Asegura que el hidden con el id de detalle se mantenga
        $data['maleta_detalle_id'] = $record->maleta_detalle_id;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // No cambiamos responsable al editar, a menos que lo requieras.
        // $data['responsable_id'] = $this->record->responsable_id;

        return $data;
    }
}
