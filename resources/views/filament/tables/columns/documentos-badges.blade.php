@php
    // La ViewColumn expone $getState() como closure -> devolvemos un array de ['nombre' => ..., 'url' => ...]
    $items   = collect($getState() ?? []);
    $limit   = 4; // cuántos badges mostrar antes del "+N"
    $visible = $items->take($limit);
    $hidden  = $items->skip($limit);
@endphp

<div x-data="{ open: false }" class="flex flex-wrap items-center gap-1 relative">
    {{-- Badges visibles --}}
    @foreach ($visible as $doc)
        @php
            $nombre = $doc['nombre'] ?? 'Documento';
            $href   = $doc['url'] ?? null;  // ya viene resuelta desde el Resource
        @endphp

        @if ($href)
            <x-filament::badge
                tag="a"
                :href="$href"
                target="_blank"
                rel="noopener noreferrer"
                color="primary"
                size="sm"
                class="max-w-[14rem] truncate"
                :tooltip="$nombre"
            >
                {{ $nombre }}
            </x-filament::badge>
        @else
            <x-filament::badge
                color="gray"
                size="sm"
                class="max-w-[14rem] truncate"
                :tooltip="$nombre"
            >
                {{ $nombre }}
            </x-filament::badge>
        @endif
    @endforeach

    {{-- Botón +N cuando hay más --}}
    @if ($hidden->isNotEmpty())
        <button type="button" x-on:click="open = !open">
            <x-filament::badge color="gray" size="sm">+{{ $hidden->count() }}</x-filament::badge>
        </button>

        {{-- Popover con el resto --}}
        <div
            x-show="open"
            x-transition
            x-on:click.outside="open = false"
            class="absolute z-10 mt-2 w-64 max-h-72 overflow-auto rounded-md border border-gray-200 bg-white p-2 shadow-lg
                   dark:bg-gray-900 dark:border-gray-700"
        >
            <div class="flex flex-col gap-1">
                @foreach ($hidden as $doc)
                    @php
                        $nombre = $doc['nombre'] ?? 'Documento';
                        $href   = $doc['url'] ?? null;
                    @endphp

                    @if ($href)
                        <x-filament::badge
                            tag="a"
                            :href="$href"
                            target="_blank"
                            rel="noopener noreferrer"
                            color="primary"
                            size="sm"
                            class="w-full justify-start"
                            :tooltip="$nombre"
                        >
                            {{ $nombre }}
                        </x-filament::badge>
                    @else
                        <x-filament::badge
                            color="gray"
                            size="sm"
                            class="w-full justify-start"
                            :tooltip="$nombre"
                        >
                            {{ $nombre }}
                        </x-filament::badge>
                    @endif
                @endforeach
            </div>
        </div>
    @endif
</div>
