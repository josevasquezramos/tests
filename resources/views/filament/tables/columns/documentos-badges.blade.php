@props(['state' => []])

<div class="flex flex-wrap gap-1">
    @foreach($state as $documento)
        <a href="{{ $documento['url'] }}" target="_blank" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800 hover:bg-primary-200">
            {{ $documento['nombre'] }}
        </a>
    @endforeach
</div>