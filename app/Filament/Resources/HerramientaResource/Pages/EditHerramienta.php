<?php

namespace App\Filament\Resources\HerramientaResource\Pages;

use App\Filament\Resources\HerramientaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHerramienta extends EditRecord
{
    protected static string $resource = HerramientaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
