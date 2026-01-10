<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-100 leading-tight">
            {{ __('Crear Nuevo Usuario') }}
        </h2>
    </x-slot>

    <div class="max-w-3xl mx-auto py-8 px-6">
        @if ($errors->any())
            <div class="mb-4 bg-red-100 dark:bg-red-900 border border-red-300 dark:border-red-700 text-red-800 dark:text-red-200 px-4 py-3 rounded-lg">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 p-6">
            <form method="POST" action="{{ route('usuarios.store') }}" class="space-y-6">
                @csrf

                <!-- Nombre -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Nombre <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           id="name"
                           name="name"
                           value="{{ old('name') }}"
                           required
                           class="w-full px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Email <span class="text-red-500">*</span>
                    </label>
                    <input type="email"
                           id="email"
                           name="email"
                           value="{{ old('email') }}"
                           required
                           class="w-full px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <!-- Contraseña -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Contraseña <span class="text-red-500">*</span>
                    </label>
                    <input type="password"
                           id="password"
                           name="password"
                           required
                           minlength="8"
                           class="w-full px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Mínimo 8 caracteres</p>
                </div>

                <!-- Confirmar Contraseña -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Confirmar Contraseña <span class="text-red-500">*</span>
                    </label>
                    <input type="password"
                           id="password_confirmation"
                           name="password_confirmation"
                           required
                           minlength="8"
                           class="w-full px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <!-- Tema -->
                <div>
                    <label for="theme" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Tema
                    </label>
                    <select id="theme"
                            name="theme"
                            class="w-full px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="light" {{ old('theme', 'light') === 'light' ? 'selected' : '' }}>Claro</option>
                        <option value="dark" {{ old('theme') === 'dark' ? 'selected' : '' }}>Oscuro</option>
                    </select>
                </div>

                <!-- Rol -->
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Rol <span class="text-red-500">*</span>
                    </label>
                    <select id="role"
                            name="role"
                            required
                            class="w-full px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Seleccione un rol</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}" {{ old('role') == $role->name ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
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
                       class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
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

