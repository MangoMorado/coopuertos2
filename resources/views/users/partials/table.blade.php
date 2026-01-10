<table class="w-full border-collapse text-sm">
    <thead class="bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 uppercase text-sm">
        <tr>
            <th class="text-left px-4 py-3">Nombre</th>
            <th class="text-left px-4 py-3">Email</th>
            <th class="text-left px-4 py-3">Rol</th>
            <th class="text-left px-4 py-3">Tema</th>
            <th class="text-left px-4 py-3">Fecha de creación</th>
            <th class="text-center px-4 py-3">Acciones</th>
        </tr>
    </thead>
    <tbody class="text-sm">
        @forelse($users as $user)
            <tr class="border-t border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $user->name }}</td>
                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $user->email }}</td>
                <td class="px-4 py-3">
                    <span class="px-2 py-1 text-xs rounded-full
                        @if($user->hasRole('Mango'))
                            bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200
                        @elseif($user->hasRole('Admin'))
                            bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200
                        @else
                            bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200
                        @endif">
                        {{ $user->roles->first()->name ?? 'Sin rol' }}
                    </span>
                </td>
                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                    <span class="px-2 py-1 text-xs rounded-full
                        {{ $user->theme === 'dark' ? 'bg-gray-800 dark:bg-gray-600 text-gray-200 dark:text-gray-100' : 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200' }}">
                        {{ $user->theme === 'dark' ? 'Oscuro' : 'Claro' }}
                    </span>
                </td>
                <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $user->created_at?->format('d/m/Y') ?? 'N/A' }}</td>
                <td class="text-center py-3">
                    <div class="flex justify-center space-x-2">
                        @can('editar usuarios')
                        <a href="{{ route('usuarios.edit', $user) }}"
                           class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded-md text-sm shadow-sm">
                            Editar
                        </a>
                        @endcan
                        @can('eliminar usuarios')
                        @if($user->id !== auth()->id())
                            <form method="POST" action="{{ route('usuarios.destroy', $user) }}" onsubmit="return confirm('¿Eliminar este usuario?')" class="inline">
                                @csrf @method('DELETE')
                                <button class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded-md text-sm shadow-sm">
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
                <td colspan="6" class="text-center py-6 text-gray-500 dark:text-gray-400">No se encontraron usuarios.</td>
            </tr>
        @endforelse
    </tbody>
</table>
