@php
    $isDark = $isDark ?? false;
    $textTitle = $isDark ? 'text-gray-100' : 'text-gray-900';
    $textSubtitle = $isDark ? 'text-gray-400' : 'text-gray-600';
    $textInput = $isDark ? 'text-gray-100' : 'text-gray-900';
    $bgInput = $isDark ? 'bg-gray-700 border-gray-600' : 'bg-white border-gray-300';
    $textLabel = $isDark ? 'text-gray-300' : 'text-gray-700';
    $textModal = $isDark ? 'text-gray-100' : 'text-gray-900';
    $textModalSub = $isDark ? 'text-gray-400' : 'text-gray-600';
@endphp

<section class="space-y-6">
    <header>
        <h2 class="text-lg font-medium {{ $textTitle }}">
            {{ __('Eliminar cuenta') }}
        </h2>

        <p class="mt-1 text-sm {{ $textSubtitle }}">
            {{ __('Al eliminar tu cuenta, todos tus datos se borrarán de forma permanente. Descarga cualquier información que quieras conservar antes de continuar.') }}
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >{{ __('Eliminar cuenta') }}</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-medium {{ $textModal }}">
                {{ __('¿Seguro que deseas eliminar tu cuenta?') }}
            </h2>

            <p class="mt-1 text-sm {{ $textModalSub }}">
                {{ __('Al eliminar tu cuenta, todos tus recursos y datos se borrarán para siempre. Ingresa tu contraseña para confirmar que deseas hacerlo.') }}
            </p>

            <div class="mt-6">
                <label for="password" class="sr-only block font-medium text-sm {{ $textLabel }}">{{ __('Contraseña') }}</label>

                <input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4 {{ $bgInput }} {{ $textInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder="{{ __('Contraseña') }}"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    {{ __('Cancelar') }}
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    {{ __('Eliminar cuenta') }}
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
