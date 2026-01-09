@php
    $theme = Auth::user()->theme ?? 'light';
    $isDark = $theme === 'dark';
    
    // Colores según el tema
    $bgCard = $isDark ? 'bg-gray-800' : 'bg-white';
    $textTitle = $isDark ? 'text-gray-100' : 'text-gray-800';
    $textSubtitle = $isDark ? 'text-gray-400' : 'text-gray-600';
    $textBody = $isDark ? 'text-gray-300' : 'text-gray-700';
    $borderCard = $isDark ? 'border-gray-700' : 'border-gray-200';
    $bgInput = $isDark ? 'bg-gray-700 border-gray-600' : 'bg-white border-gray-300';
    $bgSuccess = $isDark ? 'bg-green-900 border-green-700' : 'bg-green-100 border-green-300';
    $textSuccess = $isDark ? 'text-green-200' : 'text-green-800';
    $bgError = $isDark ? 'bg-red-900 border-red-700' : 'bg-red-100 border-red-300';
    $textError = $isDark ? 'text-red-200' : 'text-red-800';
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl {{ $textTitle }} leading-tight">
                {{ __('Usuarios') }}
            </h2>
            @can('crear usuarios')
            <a href="{{ route('users.create') }}"
               class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                {{ __('Nuevo Usuario') }}
            </a>
            @endcan
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto py-8 px-6">
        @if (session('success'))
            <div class="mb-4 {{ $bgSuccess }} border {{ $textSuccess }} px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 {{ $bgError }} border {{ $textError }} px-4 py-3 rounded-lg">
                {{ session('error') }}
            </div>
        @endif

        <div class="{{ $bgCard }} rounded-lg shadow-md border {{ $borderCard }} p-6">
            <!-- Barra de búsqueda -->
            <form method="GET" action="{{ route('users.index') }}" class="mb-6">
                <div class="flex gap-4">
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Buscar por nombre o email..."
                           class="flex-1 px-4 py-2 {{ $bgInput }} {{ $textBody }} rounded-lg border focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <button type="submit"
                            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                        Buscar
                    </button>
                    @if(request('search'))
                        <a href="{{ route('users.index') }}"
                           class="px-6 py-2 {{ $isDark ? 'bg-gray-700 hover:bg-gray-600' : 'bg-gray-300 hover:bg-gray-400' }} text-white rounded-lg transition">
                            Limpiar
                        </a>
                    @endif
                </div>
            </form>

            <!-- Tabla de usuarios -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y {{ $isDark ? 'divide-gray-700' : 'divide-gray-200' }}">
                    <thead class="{{ $isDark ? 'bg-gray-700' : 'bg-gray-50' }}">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium {{ $textSubtitle }} uppercase tracking-wider">
                                Nombre
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium {{ $textSubtitle }} uppercase tracking-wider">
                                Email
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium {{ $textSubtitle }} uppercase tracking-wider">
                                Rol
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium {{ $textSubtitle }} uppercase tracking-wider">
                                Fecha de creación
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium {{ $textSubtitle }} uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="{{ $isDark ? 'bg-gray-800 divide-gray-700' : 'bg-white divide-gray-200' }}">
                        @forelse($users as $user)
                            <tr class="{{ $isDark ? 'hover:bg-gray-700' : 'hover:bg-gray-50' }}">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium {{ $textBody }}">
                                        {{ $user->name }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm {{ $textBody }}">
                                        {{ $user->email }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                        @if($user->hasRole('Mango')) bg-purple-100 text-purple-800
                                        @elseif($user->hasRole('Admin')) bg-blue-100 text-blue-800
                                        @else bg-gray-100 text-gray-800
                                        @endif">
                                        {{ $user->roles->first()->name ?? 'Sin rol' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm {{ $textBody }}">
                                    {{ $user->created_at?->format('d/m/Y') ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end gap-2">
                                        @can('editar usuarios')
                                        <a href="{{ route('users.edit', $user) }}"
                                           class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded transition">
                                            Editar
                                        </a>
                                        @endcan
                                        @can('eliminar usuarios')
                                        @if($user->id !== auth()->id())
                                            <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline"
                                                  onsubmit="return confirm('¿Estás seguro de eliminar este usuario?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded transition">
                                                    Eliminar
                                                </button>
                                            </form>
                                        @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center {{ $textBody }}">
                                    No se encontraron usuarios.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="mt-6">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</x-app-layout>

