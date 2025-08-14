<?php

namespace App\Filament\Resources\ArticuloSalidaResource\Pages;

use App\Filament\Resources\ArticuloSalidaResource;
use App\Models\Articulo;
use App\Models\ArticuloAbierto;
use App\Models\ArticuloSalida;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateArticuloSalida extends CreateRecord
{
    protected static string $resource = ArticuloSalidaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $articulo = Articulo::find($data['articulo_id']);
        
        if ($articulo->fraccionable && ($data['modo_salida'] ?? 'normal') === 'enteros') {
            $this->handleModoEnteros($data, $articulo);
            // Detenemos el proceso normal de creación aquí
            $this->halt();
        }
        
        return $this->validacionesNormales($data, $articulo);
    }

    /***********************
     * MÉTODOS AUXILIARES *
     ***********************/

    private function handleModoEnteros(array $data, Articulo $articulo): void
    {
        $cantidadEnteros = $data['cantidad_enteros'] ?? 1;
        
        // Validación de stock
        if ($cantidadEnteros > $articulo->stock) {
            $this->mostrarError(
                'Error en cantidad',
                "No hay suficiente stock para sacar {$cantidadEnteros} unidades completas"
            );
        }

        // Creamos todas las salidas aquí mismo
        $this->crearMultiplesSalidas($data, $articulo, $cantidadEnteros);
        
        // Redireccionamos después de crear todos los registros
        $this->redirect($this->getRedirectUrl());
    }

    private function crearMultiplesSalidas(array $data, Articulo $articulo, int $cantidad): void
    {
        $registrosCreados = 0;
        
        for ($i = 0; $i < $cantidad; $i++) {
            ArticuloSalida::create([
                'articulo_id' => $articulo->id,
                'unidad_id' => $articulo->unidad_id,
                'cantidad' => $articulo->contenido,
                'precio' => $data['precio'] ?? null,
                'modo_salida' => 'enteros',
                'articulo_abierto_id' => $data['articulo_abierto_id'] ?? null,
                'created_at' => now(),
                // Agregar otros campos según sea necesario
            ]);
            $registrosCreados++;
        }

        Notification::make()
            ->title('Salidas registradas correctamente')
            ->body("Se han creado {$registrosCreados} salidas de unidades completas")
            ->success()
            ->send();
    }

    private function validacionesNormales(array $data, Articulo $articulo): array
    {
        if (!$articulo->fraccionable) {
            if ($data['cantidad'] > $articulo->stock) {
                $this->mostrarError(
                    'Error en cantidad',
                    'La cantidad excede el stock disponible'
                );
            }
            return $data;
        }
        
        if ($data['articulo_abierto_id']) {
            $this->validarUnidadAbierta($data);
        } else {
            $this->validarNuevaUnidad($data, $articulo);
        }
        
        return $data;
    }

    private function validarUnidadAbierta(array $data): void
    {
        $abierto = ArticuloAbierto::find($data['articulo_abierto_id']);
        if ($data['cantidad'] > $abierto->restante) {
            $this->mostrarError(
                'Error en cantidad',
                'La cantidad excede el contenido restante de la unidad abierta'
            );
        }
    }

    private function validarNuevaUnidad(array $data, Articulo $articulo): void
    {
        if ($data['cantidad'] > $articulo->contenido) {
            $this->mostrarError(
                'Error en cantidad',
                'La cantidad excede el contenido de una unidad'
            );
        }
        
        if ($articulo->stock < 1) {
            $this->mostrarError(
                'Error en stock',
                'No hay stock suficiente para abrir una nueva unidad'
            );
        }
    }

    private function mostrarError(string $titulo, string $mensaje): void
    {
        Notification::make()
            ->title($titulo)
            ->body('<div>'.$mensaje.'<br><img src="'.asset('img/daniel.jpeg').'" style="max-width: 250px; margin-top: 10px;"></div><br>Digita bien los datos, por favor, varón')
            ->danger()
            ->send();
        $this->halt();
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }
}