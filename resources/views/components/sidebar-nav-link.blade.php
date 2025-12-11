@props(['active', 'theme' => 'dark'])

@php
    $isDark = $theme === 'dark';
    
    if ($active ?? false) {
        $classes = $isDark 
            ? 'flex items-center gap-3 px-4 py-3 rounded-lg bg-gray-700 text-white font-medium transition-colors duration-200'
            : 'flex items-center gap-3 px-4 py-3 rounded-lg bg-gray-100 text-gray-900 font-medium transition-colors duration-200';
    } else {
        $classes = $isDark
            ? 'flex items-center gap-3 px-4 py-3 rounded-lg text-gray-300 hover:bg-gray-700 hover:text-white transition-colors duration-200'
            : 'flex items-center gap-3 px-4 py-3 rounded-lg text-gray-600 hover:bg-gray-100 hover:text-gray-900 transition-colors duration-200';
    }
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    @isset($icon)
        <span class="flex-shrink-0">
            {{ $icon }}
        </span>
    @endisset
    <span>{{ $slot }}</span>
</a>
