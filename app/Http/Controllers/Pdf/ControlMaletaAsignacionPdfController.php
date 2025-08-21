<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Models\ControlMaleta;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ControlMaletaAsignacionPdfController extends Controller
{
    public function show(ControlMaleta $control)
    {
        // Cargar relaciones
        $control->load([
            'maleta.propietario',
            'responsable',
            'propietario',
            'detalles.herramienta',
        ]);

        // Ordenamos los detalles por nombre de herramienta (case-insensitive)
        $detalles = $control->detalles
            ->sortBy(fn($d) => mb_strtoupper($d->herramienta->nombre ?? ''), SORT_NATURAL)
            ->values();

        $pdf = Pdf::loadView('pdf.hoja_asignacion', [
            'control' => $control,
            'maleta' => $control->maleta,
            'detalles' => $detalles,
            'generatedAt' => now(),
        ])->setPaper('A4', 'portrait');

        $maletaCodigo = $control->maleta?->codigo ?? 'SIN-CODIGO';
        $fecha = $control->fecha?->format('Ymd') ?? now()->format('Ymd');

        // Abrir inline en nueva pestaña
        return $pdf->stream("hoja-asignacion-{$maletaCodigo}-{$fecha}.pdf");
    }
}
