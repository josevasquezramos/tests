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
        // Responsable = usuario actual
        $data['responsable_id'] = auth()->id();

        // Ajustar campos según el tipo de origen
        if ($data['tipo_origen'] === 'MALETA') {
            // Para MALETA: cantidad siempre es 1
            $data['cantidad'] = 1;
            // herramienta_id se debe obtener del maleta_detalle
            if (!empty($data['maleta_detalle_id'])) {
                $maletaDetalle = \App\Models\MaletaDetalle::find($data['maleta_detalle_id']);
                $data['herramienta_id'] = $maletaDetalle?->herramienta_id;
            }
            // propietario_id ya viene del form (hidden field)
        } else {
            // Para STOCK: limpiar campos de maleta
            $data['maleta_detalle_id'] = null;
            $data['propietario_id'] = null;
            // cantidad y herramienta_id ya vienen correctos del form
        }

        // Limpiar campos temporales del UI
        unset($data['maleta_id']);
        unset($data['responsable_nombre']);
        unset($data['propietario_nombre']);
        unset($data['max_cantidad']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}