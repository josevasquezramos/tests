<?php

namespace App\Filament\Resources\ArticuloSalidaResource\Pages;

use App\Filament\Resources\ArticuloSalidaResource;
use App\Models\Articulo;
use App\Models\ArticuloAbierto;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditArticuloSalida extends EditRecord
{
    protected static string $resource = ArticuloSalidaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $articulo = $this->record->articulo;
        
        // Solo para no fraccionables: stock_actual = stock + cantidad original
        $data['stock_actual'] = $articulo->fraccionable 
            ? $articulo->stock 
            : $articulo->stock + $this->record->cantidad;

        $data['_original_record'] = $this->record->getOriginal();
        
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $articulo = $this->record->articulo;
        $originalCantidad = $this->record->cantidad;
        $nuevaCantidad = $data['cantidad'];

        // Validación para no fraccionables
        if (!$articulo->fraccionable) {
            $stockDisponible = $articulo->stock + $originalCantidad;
            if ($nuevaCantidad > $stockDisponible) {
                Notification::make()
                    ->title('Error en cantidad')
                    ->body('<div>La cantidad excede el stock disponible<br><img src="'.asset('img/daniel.jpeg').'" style="width: 250px; margin-top: 10px;"></div><br>Digita bien los datos, por favor, varón')
                    ->danger()
                    ->send();
                $this->halt();
            }
            return $data;
        }

        // Validación para fraccionables con abierto
        $abierto = ArticuloAbierto::withTrashed()->find($this->record->articulo_abierto_id);
        $restanteDisponible = $abierto->restante + $originalCantidad;
        if ($nuevaCantidad > $restanteDisponible) {
            Notification::make()
                ->title('Error en cantidad')
                ->body('<div>La cantidad excede el contenido restante de la unidad abierta<br><img src="'.asset('img/daniel.jpeg').'" style="width: 250px; margin-top: 10px;"></div><br>Digita bien los datos, por favor, varón')
                ->danger()
                ->send();
            $this->halt();
        }

        return $data;
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->title('Salida actualizada')
            ->body('La salida de artículo se ha actualizado correctamente.')
            ->success();
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }
}
