<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Models\Maleta;
use Barryvdh\DomPDF\Facade\Pdf;

class MaletaPdfController extends Controller
{
    public function show(Maleta $maleta)
    {
        $maleta->load(['propietario', 'detalles.herramienta']);

        $normalizeSpaces = fn(string $s) => trim(preg_replace('/\s+/u', ' ', $s));
        $trimPunctuation = fn(string $s) => trim($s, " \t\n\r\0\x0B.:,;");

        // Función mejorada para dividir base y sufijo
        $splitBaseSuffix = function (string $name) use ($normalizeSpaces, $trimPunctuation): array {
            $name = $trimPunctuation($normalizeSpaces($name));
            if ($name === '') {
                return ['base' => '—', 'suffix' => null];
            }

            $tokens = explode(' ', $name);
            $n = count($tokens);

            if ($n === 1) {
                return ['base' => $tokens[0], 'suffix' => null];
            }

            // Detección de herramientas conocidas con reglas específicas
            $firstToken = mb_strtoupper($tokens[0]);
            $secondToken = $n > 1 ? mb_strtoupper($tokens[1]) : '';
            
            // Reglas para herramientas específicas
            if ($firstToken === 'ALICATE') {
                return ['base' => 'ALICATE', 'suffix' => implode(' ', array_slice($tokens, 1))];
            }
            
            if ($firstToken === 'DESARMADOR' && $secondToken === 'DIELÉCTRICO') {
                return ['base' => 'DESARMADOR DIELÉCTRICO', 'suffix' => implode(' ', array_slice($tokens, 2))];
            }
            
            if ($firstToken === 'LLAVE' && $n >= 4 && mb_strtoupper($tokens[$n-2]) === 'B') {
                return ['base' => implode(' ', array_slice($tokens, 0, $n-1)), 'suffix' => $tokens[$n-1]];
            }
            
            if ($firstToken === 'SUPLETE' && $secondToken === 'DE') {
                $measure = $n > 2 ? $tokens[2] : '';
                return ['base' => "SUPLETE DE $measure", 'suffix' => implode(' ', array_slice($tokens, 3))];
            }

            // Algoritmo general para otras herramientas
            // Encuentra el prefijo común más largo que termina en una palabra completa
            $lastToken = $tokens[$n-1];
            
            // Si el último token es numérico o una medida, se considera sufijo
            if (preg_match('/^\d+(?:[.,]\d+)?$|^\d+\/\d+$|^(CHICO|MEDIANO|GRANDE|PEQUEÑO|EXTRA|STANDARD)$/iu', $lastToken)) {
                return [
                    'base' => implode(' ', array_slice($tokens, 0, $n-1)),
                    'suffix' => $lastToken
                ];
            }
            
            // Si el penúltimo token es numérico y el último es una unidad
            if ($n >= 2 && preg_match('/^\d+(?:[.,]\d+)?$|^\d+\/\d+$/u', $tokens[$n-2]) && 
                preg_match('/^[A-Za-z]{1,12}$/u', $lastToken)) {
                return [
                    'base' => implode(' ', array_slice($tokens, 0, $n-2)),
                    'suffix' => $tokens[$n-2] . ' ' . $lastToken
                ];
            }

            // Por defecto, tomar el primer token como base y el resto como sufijo
            return [
                'base' => $tokens[0],
                'suffix' => implode(' ', array_slice($tokens, 1))
            ];
        };

        // --- AGRUPACIÓN ---
        $groups = [];

        foreach ($maleta->detalles as $detalle) {
            $nombre = $detalle->herramienta->nombre ?? '—';
            $nombre = $normalizeSpaces($nombre);

            $split = $splitBaseSuffix($nombre);

            // Normalizar a mayúsculas para agrupar
            $baseUpper = mb_strtoupper($split['base']);
            $suffixUpper = $split['suffix'] ? mb_strtoupper($split['suffix']) : null;

            if (!isset($groups[$baseUpper])) {
                $groups[$baseUpper] = [
                    'base_upper' => $baseUpper,
                    'count' => 0,
                    'suffixes' => [],
                ];
            }

            $groups[$baseUpper]['count'] += 1;

            if ($suffixUpper !== null && $suffixUpper !== '') {
                if (!isset($groups[$baseUpper]['suffixes'][$suffixUpper])) {
                    $groups[$baseUpper]['suffixes'][$suffixUpper] = 0;
                }
                $groups[$baseUpper]['suffixes'][$suffixUpper] += 1;
            }
        }

        // Normalizar a arrays, ordenar sufijos y ordenar grupos por nombre base
        $grupos = [];
        foreach ($groups as $g) {
            // Ordenar sufijos alfabéticamente
            $suffixList = array_keys($g['suffixes']);
            sort($suffixList);

            // Preparar lista de visualización con conteos en sufijos repetidos
            $displaySuffixes = array_map(function ($suf) use ($g) {
                $cnt = $g['suffixes'][$suf] ?? 1;
                return $cnt > 1 ? $suf . ' (' . $cnt . ')' : $suf;
            }, $suffixList);

            $grupos[] = [
                'base_upper' => $g['base_upper'],
                'count' => $g['count'],
                'suffixes' => $displaySuffixes,
            ];
        }

        // Ordenar grupos alfabéticamente
        usort($grupos, fn($a, $b) => strcmp($a['base_upper'], $b['base_upper']));

        // Renderizar el PDF
        $pdf = Pdf::loadView('pdf.maleta', [
            'maleta' => $maleta,
            'grupos' => $grupos,
            'generatedAt' => now(),
        ])->setPaper('A4', 'portrait');

        return $pdf->stream("maleta-{$maleta->codigo}.pdf");
    }
}