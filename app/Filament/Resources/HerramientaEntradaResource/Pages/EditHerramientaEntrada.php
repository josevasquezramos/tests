<?php

namespace App\Filament\Resources\HerramientaEntradaResource\Pages;

use App\Filament\Resources\HerramientaEntradaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHerramientaEntrada extends EditRecord
{
    protected static string $resource = HerramientaEntradaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->record;
        $data['responsable_nombre'] = $record->responsable?->name;
        return $data;
    }
}
