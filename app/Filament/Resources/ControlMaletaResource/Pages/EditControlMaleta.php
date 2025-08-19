<?php

namespace App\Filament\Resources\ControlMaletaResource\Pages;

use App\Filament\Resources\ControlMaletaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditControlMaleta extends EditRecord
{
    protected static string $resource = ControlMaletaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['responsable_nombre'] = $this->record->responsable?->name;
        $data['propietario_nombre'] = $this->record->propietario?->name;
        $data['maleta_codigo'] = $this->record->maleta?->codigo;

        return $data;
    }
}
