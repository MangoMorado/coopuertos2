@props(['active', 'title' => null])

@php
    if ($active ?? false) {
        $classes = 'flex items-center gap-3 rounded-lg bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white font-medium transition-all duration-200';
    } else {
        $classes = 'flex items-center gap-3 rounded-lg text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition-all duration-200';
    }
@endphp

<a 
    {{ $attributes->merge(['class' => $classes]) }}
    x-data="{ isMobile: window.innerWidth < 768 }"
    x-init="isMobile = window.innerWidth < 768; window.addEventListener('resize', () => { isMobile = window.innerWidth < 768; })"
    :class="{ 
        // Móvil: siempre solo iconos (justify-center), desktop: colapsado = solo iconos
        'justify-center px-3 py-3': isMobile || (!isMobile && $store.sidebar.collapsed),
        'px-4 py-3': !isMobile && !$store.sidebar.collapsed
    }"
>
    @isset($icon)
        <span class="flex-shrink-0 flex items-center justify-center">
            {{ $icon }}
        </span>
    @endisset
    <span 
        class="truncate transition-opacity duration-300"
        x-show="!isMobile && !$store.sidebar.collapsed"
        style="display: none;"
    >
        {{ $slot }}
    </span>
</a>
