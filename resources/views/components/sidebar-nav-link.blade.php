@props(['active', 'theme' => 'dark', 'title' => null])

@php
    $isDark = $theme === 'dark';
    
    if ($active ?? false) {
        $classes = $isDark 
            ? 'flex items-center gap-3 rounded-lg bg-gray-700 text-white font-medium transition-all duration-200'
            : 'flex items-center gap-3 rounded-lg bg-gray-100 text-gray-900 font-medium transition-all duration-200';
    } else {
        $classes = $isDark
            ? 'flex items-center gap-3 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white transition-all duration-200'
            : 'flex items-center gap-3 rounded-lg text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-all duration-200';
    }
@endphp

<a 
    {{ $attributes->merge(['class' => $classes]) }}
    :class="{ 
        'justify-center px-3 py-3': $store.sidebar.collapsed && window.innerWidth >= 768,
        'px-4 py-3': !$store.sidebar.collapsed || window.innerWidth < 768
    }"
>
    @isset($icon)
        <span class="flex-shrink-0 flex items-center justify-center">
            {{ $icon }}
        </span>
    @endisset
    <span 
        class="truncate transition-opacity duration-300"
        x-bind:class="{ 
            'opacity-0 w-0 hidden': $store.sidebar.collapsed && window.innerWidth >= 768, 
            'opacity-100': !$store.sidebar.collapsed || window.innerWidth < 768 
        }"
    >
        {{ $slot }}
    </span>
</a>
