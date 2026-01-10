{{-- Skeleton Loader para Tarjetas --}}
<div class="bg-white dark:bg-gray-800 p-6 shadow rounded-lg border border-gray-200 dark:border-gray-700">
    {{-- Título --}}
    @if(!isset($hideTitle) || !$hideTitle)
        <div class="h-5 bg-gray-200 dark:bg-gray-700 rounded w-3/4 mb-4 animate-skeleton-pulse"></div>
    @endif
    
    {{-- Contenido principal --}}
    <div class="space-y-3">
        @if($lines ?? 3)
            @for($i = 0; $i < ($lines ?? 3); $i++)
                <div 
                    class="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-skeleton-pulse"
                    style="width: {{ $i === (($lines ?? 3) - 1) ? '60%' : '100%' }}; animation-delay: {{ $i * 0.1 }}s;"
                ></div>
            @endfor
        @else
            {{-- Si no se especifica líneas, mostrar un número grande (para dashboards) --}}
            <div class="h-12 bg-gray-200 dark:bg-gray-700 rounded w-1/3 mb-4 animate-skeleton-pulse"></div>
            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-2/3 animate-skeleton-pulse" style="animation-delay: 0.1s;"></div>
        @endif
    </div>
    
    {{-- Botones o acciones opcionales --}}
    @if(isset($showActions) && $showActions)
        <div class="mt-4 flex space-x-2">
            <div class="h-10 bg-gray-200 dark:bg-gray-700 rounded w-24 animate-skeleton-pulse"></div>
            <div class="h-10 bg-gray-200 dark:bg-gray-700 rounded w-24 animate-skeleton-pulse" style="animation-delay: 0.1s;"></div>
        </div>
    @endif
</div>
