<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Hoja de asignación - {{ $maleta->codigo ?? '—' }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h1 { font-size: 18px; margin: 0 0 8px; }
        .meta { margin-bottom: 10px; }
        .meta p { margin: 2px 0; }

        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #000; padding: 4px 6px; vertical-align: middle; }
        th { text-align: center; font-weight: bold; }

        /* Anchos de columnas (porcentaje para dompdf) */
        .col-num { width: 5%;  text-align: center; }
        .col-herr { width: 27%; word-break: break-word; overflow-wrap: anywhere; white-space: normal; }
        .col-op, .col-mer, .col-per { width: 9%; text-align: center; }
        .col-obs { width: 41%; } /* Observación más ancha */

        /* Cajita para marcar (pequeña) */
        .box {
            display: inline-block;
            width: 11px; height: 11px;
            border: 1px solid #000;
            line-height: 11px;
        }

        /* Firmas */
        .sign-row { margin-top: 18px; }
        .sign-table { width:100%; border:none; border-collapse:separate; }
        .sign-cell { width: 33%; text-align: center; vertical-align: bottom; }
        .sign-line { border-top: 1px solid #000; margin: 40px 16px 4px; }
        .sign-name { font-size: 12px; margin-top: 2px; }
        .sign-role { font-size: 11px; }
    </style>
</head>
<body>
    <h1>HOJA DE ASIGNACIÓN</h1>

    <div class="meta">
        <p><strong>Fecha:</strong> {{ optional($control->fecha)->translatedFormat('d \d\e F \d\e\l Y') ?? '—' }}</p>
        <p><strong>Responsable:</strong> {{ $control->responsable->name ?? '—' }}</p>
        <p><strong>Propietario:</strong> {{ $control->propietario->name ?? ($maleta->propietario->name ?? '—') }}</p>
        <p><strong>Maleta (código):</strong> {{ $maleta->codigo ?? '—' }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th class="col-num">#</th>
                <th class="col-herr">Herramienta</th>
                <th class="col-op">Operativo</th>
                <th class="col-mer">Merma</th>
                <th class="col-per">Perdido</th>
                <th class="col-obs">Observación</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($detalles as $idx => $detalle)
                <tr>
                    <td class="col-num">{{ $idx + 1 }}</td>
                    <td class="col-herr">{{ $detalle->herramienta->nombre ?? '—' }}</td>
                    <td class="col-op"><span class="box"></span></td>
                    <td class="col-mer"><span class="box"></span></td>
                    <td class="col-per"><span class="box"></span></td>
                    <td class="col-obs">&nbsp;</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center;">No hay herramientas registradas para este control.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="sign-row">
        <table class="sign-table">
            <tr>
                <td class="sign-cell">
                    <div class="sign-line"></div>
                    <div class="sign-name">{{ $control->responsable->name ?? '—' }}</div>
                    <div class="sign-role">Responsable</div>
                </td>
                <td class="sign-cell">
                    <div class="sign-line"></div>
                    <div class="sign-name">{{ $control->propietario->name ?? ($maleta->propietario->name ?? '—') }}</div>
                    <div class="sign-role">Propietario / Colaborador</div>
                </td>
                <td class="sign-cell">
                    <div class="sign-line"></div>
                    <div class="sign-name">PRUEBA</div>
                    <div class="sign-role">Supervisor</div>
                </td>
            </tr>
        </table>
    </div>

    <p style="margin-top:10px; font-size:11px;">
        <strong>Generado:</strong> {{ $generatedAt->format('d/m/Y H:i') }}
    </p>
</body>
</html>
