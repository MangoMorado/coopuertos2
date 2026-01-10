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
    $bgError = $isDark ? 'bg-red-900 border-red-700' : 'bg-red-100 border-red-300';
    $textError = $isDark ? 'text-red-200' : 'text-red-800';
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl {{ $textTitle }} leading-tight">
            {{ __('Crear Nuevo Usuario') }}
        </h2>
    </x-slot>

    <div class="max-w-3xl mx-auto py-8 px-6">
        @if ($errors->any())
            <div class="mb-4 {{ $bgError }} border {{ $textError }} px-4 py-3 rounded-lg">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="{{ $bgCard }} rounded-lg shadow-md border {{ $borderCard }} p-6">
            <form method="POST" action="{{ route('usuarios.store') }}" class="space-y-6">
                @csrf

                <!-- Nombre -->
                <div>
                    <label for="name" class="block text-sm font-medium {{ $textBody }} mb-2">
                        Nombre <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           id="name"
                           name="name"
                           value="{{ old('name') }}"
                           required
                           class="w-full px-4 py-2 {{ $bgInput }} {{ $textBody }} rounded-lg border focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium {{ $textBody }} mb-2">
                        Email <span class="text-red-500">*</span>
                    </label>
                    <input type="email"
                           id="email"
                           name="email"
                           value="{{ old('email') }}"
                           required
                           class="w-full px-4 py-2 {{ $bgInput }} {{ $textBody }} rounded-lg border focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <!-- Contraseña -->
                <div>
                    <label for="password" class="block text-sm font-medium {{ $textBody }} mb-2">
                        Contraseña <span class="text-red-500">*</span>
                    </label>
                    <input type="password"
                           id="password"
                           name="password"
                           required
                           minlength="8"
                           class="w-full px-4 py-2 {{ $bgInput }} {{ $textBody }} rounded-lg border focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="mt-1 text-sm {{ $textSubtitle }}">Mínimo 8 caracteres</p>
                </div>

                <!-- Confirmar Contraseña -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium {{ $textBody }} mb-2">
                        Confirmar Contraseña <span class="text-red-500">*</span>
                    </label>
                    <input type="password"
                           id="password_confirmation"
                           name="password_confirmation"
                           required
                           minlength="8"
                           class="w-full px-4 py-2 {{ $bgInput }} {{ $textBody }} rounded-lg border focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <!-- Rol -->
                <div>
                    <label for="role" class="block text-sm font-medium {{ $textBody }} mb-2">
                        Rol <span class="text-red-500">*</span>
                    </label>
                    <select id="role"
                            name="role"
                            required
                            class="w-full px-4 py-2 {{ $bgInput }} {{ $textBody }} rounded-lg border focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Seleccione un rol</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}" {{ old('role') == $role->name ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-sm {{ $textSubtitle }}">
                        @if(auth()->user()->hasRole('Mango'))
                            Puedes crear usuarios con cualquier rol.
                        @elseif(auth()->user()->hasRole('Admin'))
                            Solo puedes crear usuarios con rol User.
                        @endif
                    </p>
                </div>

                <!-- Botones -->
                <div class="flex justify-end space-x-4 pt-4">
                    <a href="{{ route('usuarios.index') }}"
                       class="px-6 py-2 {{ $isDark ? 'bg-gray-600 hover:bg-gray-700' : 'bg-gray-600 hover:bg-gray-700' }} text-white rounded-lg transition">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                        Crear Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>

