{{-- Skeleton Loader para Formularios --}}
<div class="space-y-6">
    @for($i = 0; $i < ($fields ?? 6); $i++)
        <div>
            {{-- Label --}}
            <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded w-1/4 mb-2 animate-skeleton-pulse" style="animation-delay: {{ $i * 0.05 }}s;"></div>
            
            {{-- Input --}}
            <div class="h-10 bg-gray-200 dark:bg-gray-700 rounded w-full animate-skeleton-pulse" style="animation-delay: {{ $i * 0.05 + 0.1 }}s;"></div>
        </div>
    @endfor
    
    {{-- Botones de acción --}}
    <div class="flex space-x-2 pt-4">
        <div class="h-10 bg-gray-200 dark:bg-gray-700 rounded w-24 animate-skeleton-pulse"></div>
        <div class="h-10 bg-gray-200 dark:bg-gray-700 rounded w-24 animate-skeleton-pulse" style="animation-delay: 0.1s;"></div>
    </div>
</div>
