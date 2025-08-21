<?php

namespace App\Filament\Resources\MaletaResource\Pages;

use App\Filament\Resources\MaletaResource;
use App\Models\Maleta;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMaleta extends EditRecord
{
    protected static string $resource = MaletaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('pdf')
                ->label('Acta de entrega')
                ->icon('heroicon-o-document-text')
                ->url(fn(Maleta $record) => route('pdf.maleta', $record))
                ->openUrlInNewTab(),
            // Actions\DeleteAction::make(),
        ];
    }
}
