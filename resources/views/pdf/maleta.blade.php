<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Maleta {{ $maleta->codigo }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
        }

        ul {
            margin: 0;
            padding-left: 18px;
        }

        li {
            margin-bottom: 4px;
        }
    </style>
</head>

<body>
    <h1>ACTA DE ENTREGA - MALETA DE HERRAMIENTAS</h1>

    <ol>
        <li>
            <p><b>Información general</b></p>
            <p>Por la presente se hace constancia de la entrega de maletas de herramientas para que cada trabajador
                pueda ejecutar sus labores. Se deberá cuidar minuciosamente cada herramienta y se deberá informar al
                área
                correspondiente de algún imprevisto (fracturado, reventador, deteriorado, etc). Esta lista deberá ser
                presentada en cada supervisión.</p>
        </li>

        <li>
            <p><b>Información de datos</b></p>
            <ul>
                <li><b>Nombres de quién recibió:</b> {{ $maleta->propietario->name ?? '—' }}</li>
                <li><b>Nombres de quién entrega:</b> {{ Auth::user()->name }}</li>
                <li><b>Fecha de entrega:</b> {{ $generatedAt->translatedFormat('d \d\e F \d\e\l Y') }}</li>
                <li><b>Codificación:</b> {{ $maleta->codigo ?? '—' }}</li>
            </ul>
        </li>

        <li>
            <p><b>Información de herramientas</b></p>
            <p>Total de herramientas: {{ $maleta->detalles->count() }}</p>

            @if(empty($grupos))
                <p>No hay herramientas registradas.</p>
            @else
                <ul>
                    @foreach($grupos as $g)
                        @php
                            $tiene = !empty($g['suffixes']);
                            $lista = $tiene ? implode(', ', $g['suffixes']) : '';
                        @endphp
                        <li>
                            {{ $g['count'] }} x {{ $g['base_upper'] }}@if($tiene): {{ $lista }}.@else.@endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </li>
    </ol>

    <table style="border-collapse: collapse; width: 100%;">
        <tr>
            <td style="width: 21%; padding-top: 50px;"></td>
            <td style="width: 31%; border-bottom: 2px solid black;"></td>
            <td style="width: 21%;"></td>
            <td style="width: 31%; border-bottom: 2px solid black;"></td>
            <td style="width: 21%;"></td>
        </tr>
        <tr>
            <td></td>
            <td style="text-align: center; padding-top: 10px;">{{ $maleta->propietario->name ?? '—' }}</td>
            <td></td>
            <td style="text-align: center; padding-top: 10px;">{{ Auth::user()->name }}</td>
            <td></td>
        </tr>
    </table>
</body>

</html>