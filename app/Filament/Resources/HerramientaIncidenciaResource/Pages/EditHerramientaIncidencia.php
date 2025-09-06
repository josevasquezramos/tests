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

        // Campos comunes
        $data['responsable_nombre'] = $record->responsable?->name;
        $data['tipo_origen_display'] = $record->tipo_origen === 'MALETA' ? 'Desde Maleta' : 'Desde Stock/Almacén';
        $data['motivo_display'] = $record->motivo;

        if ($record->tipo_origen === 'MALETA') {
            // Datos específicos de MALETA
            $data['propietario_nombre'] = $record->propietario?->name ?? 'No asignado';

            // Obtener maleta y herramienta desde maleta_detalle
            $md = $record->maletaDetalle()->withTrashed()->with(['maleta', 'herramienta'])->first();

            $data['maleta_codigo'] = $md?->maleta?->codigo ?? "Maleta #{$md?->maleta_id}";
            $data['herramienta_nombre'] = $md?->herramienta?->nombre ?? "Detalle #{$record->maleta_detalle_id}";

        } else {
            // Datos específicos de STOCK
            $data['herramienta_nombre'] = $record->herramienta?->nombre ?? "Herramienta #{$record->herramienta_id}";
            $data['cantidad_display'] = $record->cantidad;
            // Para STOCK, no mostrar información de maleta
            $data['maleta_codigo'] = null;
            $data['propietario_nombre'] = 'N/A'; // O puedes dejarlo null
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // En edición, según los triggers SQL actuales que propusiste,
        // no se permite editar ciertos campos de las incidencias.
        // Por seguridad, mantener solo los campos editables

        $allowedFields = ['motivo', 'observacion'];

        // Filtrar solo campos permitidos
        $filteredData = array_intersect_key($data, array_flip($allowedFields));

        // Mantener los demás campos del registro original
        foreach ($this->record->getAttributes() as $key => $value) {
            if (!in_array($key, $allowedFields)) {
                $filteredData[$key] = $value;
            }
        }

        return $filteredData;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}