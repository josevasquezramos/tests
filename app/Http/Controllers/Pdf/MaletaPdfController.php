<?php

namespace App\Http\Controllers\Pdf;

use App\Http\Controllers\Controller;
use App\Models\Maleta;
use Barryvdh\DomPDF\Facade\Pdf;

class MaletaPdfController extends Controller
{
    public function show(Maleta $maleta)
    {
        // Cargar relaciones necesarias
        $maleta->load(['propietario', 'detalles.herramienta']);

        $normalizeSpaces = fn (string $s) => trim(preg_replace('/\s+/u', ' ', $s));
        $trimPunctuation = fn (string $s) => trim($s, " \t\n\r\0\x0B.:,;");

        /**
         * Divide en: BASE (prefijo común) + SUFIJO (resto).
         * Regla práctica:
         *  - SUFIJO es normalmente el último token.
         *  - Si hay patrón número+unidad separados (ej. "10 mm"), se toma ambos como un único sufijo.
         *  - Si viene junto (ej. "10mm"), también se considera un solo sufijo.
         *  - Fracciones tipo "1/2" se tratan como sufijo numérico.
         */
        $splitBaseSuffix = function (string $name) use ($normalizeSpaces, $trimPunctuation): array {
            $name = $trimPunctuation($normalizeSpaces($name));
            if ($name === '') {
                return ['base' => '—', 'suffix' => null];
            }

            $tokens = explode(' ', $name);
            $n = count($tokens);
            if ($n === 1) {
                // No hay sufijo claro
                return ['base' => $tokens[0], 'suffix' => null];
            }

            $last = $trimPunctuation(end($tokens));
            $beforeLast = $n >= 2 ? $trimPunctuation($tokens[$n - 2]) : '';

            // Caso 1: número + unidad separados: "10 mm"
            if (
                preg_match('/^\d+(?:[.,]\d+)?$/u', $beforeLast) &&
                preg_match('/^[A-Za-z]{1,12}$/u', $last)
            ) {
                $baseTokens = array_slice($tokens, 0, $n - 2);
                $suffix = $beforeLast . ' ' . $last;
            }
            // Caso 2: número+unidad pegados: "10mm"
            elseif (preg_match('/^(\d+(?:[.,]\d+)?)([A-Za-z]{1,12})$/u', $last, $m)) {
                $baseTokens = array_slice($tokens, 0, $n - 1);
                $suffix = $m[1] . $m[2];
            }
            // Caso 3: fracción como "1/2"
            elseif (preg_match('/^\d+\s*\/\s*\d+$/u', $last)) {
                $baseTokens = array_slice($tokens, 0, $n - 1);
                $suffix = preg_replace('/\s*/', '', $last); // "1/2"
            }
            // Por defecto: último token como sufijo
            else {
                $baseTokens = array_slice($tokens, 0, $n - 1);
                $suffix = $last;
            }

            $base = $normalizeSpaces(implode(' ', $baseTokens));
            if ($base === '') {
                // Si por algún motivo la base queda vacía, usa el nombre original sin sufijo
                $base = $name;
                $suffix = null;
            }

            return ['base' => $base, 'suffix' => $suffix];
        };

        // Parser para ordenar "sufijos numéricos" primero y luego alfabéticos
        $parseNumericish = function (string $s): ?array {
            $u = trim($s);

            // Fracción "a/b"
            if (preg_match('/^\d+\s*\/\s*\d+$/u', $u)) {
                [$a, $b] = array_map('trim', preg_split('/\//', $u));
                $val = ((float) $a) / max(((float) $b), 1e-9);
                return ['type' => 'num', 'val' => $val, 'unit' => ''];
            }

            // número (con , o .) + unidad opcional (con o sin espacio)
            if (preg_match('/^(\d+(?:[.,]\d+)?)(?:\s*([A-Za-z]{0,12}))?$/u', $u, $m)) {
                $val = (float) str_replace(',', '.', $m[1]);
                $unit = strtoupper($m[2] ?? '');
                return ['type' => 'num', 'val' => $val, 'unit' => $unit];
            }

            return null; // no numérico
        };

        $suffixComparator = function (string $a, string $b) use ($parseNumericish): int {
            $pa = $parseNumericish($a);
            $pb = $parseNumericish($b);

            if ($pa && $pb) {
                if ($pa['val'] < $pb['val']) return -1;
                if ($pa['val'] > $pb['val']) return 1;
                return strcasecmp($pa['unit'], $pb['unit']); // desempate por unidad
            }

            if ($pa && !$pb) return -1; // numéricos primero
            if (!$pa && $pb) return 1;

            return strcasecmp($a, $b); // ambos no numéricos
        };

        // --- AGRUPACIÓN ---
        $groups = [];

        foreach ($maleta->detalles as $detalle) {
            $nombre = $detalle->herramienta->nombre ?? '—';
            $nombre = $normalizeSpaces($nombre);

            $split = $splitBaseSuffix($nombre);

            // Todo en MAYÚSCULAS para mostrar y agrupar
            $baseUpper = mb_strtoupper($split['base']);
            $suffixUpper = is_null($split['suffix']) ? null : mb_strtoupper($split['suffix']);

            if (!isset($groups[$baseUpper])) {
                $groups[$baseUpper] = [
                    'base_upper' => $baseUpper,
                    'count'      => 0,
                    'suffixes'   => [], // mapa: sufijo => cantidad
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
            // Ordenar sufijos con el comparador
            $suffixList = array_keys($g['suffixes']);
            usort($suffixList, $suffixComparator);

            // Preparar lista de visualización con conteos en sufijos repetidos
            $displaySuffixes = array_map(function ($suf) use ($g) {
                $cnt = $g['suffixes'][$suf] ?? 1;
                return $cnt > 1 ? $suf . ' (' . $cnt . ')' : $suf;
            }, $suffixList);

            $grupos[] = [
                'base_upper' => $g['base_upper'],
                'count'      => $g['count'],
                'suffixes'   => $displaySuffixes, // ya únicos, ordenados y con (n) si aplica
            ];
        }

        usort($grupos, fn ($a, $b) => strcmp($a['base_upper'], $b['base_upper']));

        // Renderizar el PDF
        $pdf = Pdf::loadView('pdf.maleta', [
            'maleta'      => $maleta,
            'grupos'      => $grupos,
            'generatedAt' => now(),
        ])->setPaper('A4', 'portrait');

        return $pdf->stream("maleta-{$maleta->codigo}.pdf");
    }
}
