@php
    $isDark = $isDark ?? false;
    $textTitle = $isDark ? 'text-gray-100' : 'text-gray-900';
    $textSubtitle = $isDark ? 'text-gray-400' : 'text-gray-600';
    $textInput = $isDark ? 'text-gray-100' : 'text-gray-900';
    $bgInput = $isDark ? 'bg-gray-700 border-gray-600' : 'bg-white border-gray-300';
    $textLabel = $isDark ? 'text-gray-300' : 'text-gray-700';
    $textSuccess = $isDark ? 'text-gray-400' : 'text-gray-600';
@endphp

<section>
    <header>
        <h2 class="text-lg font-medium {{ $textTitle }}">
            {{ __('Actualizar contraseña') }}
        </h2>

        <p class="mt-1 text-sm {{ $textSubtitle }}">
            {{ __('Asegúrate de usar una contraseña larga y aleatoria para mantener tu cuenta segura.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div>
            <label for="update_password_current_password" class="block font-medium text-sm {{ $textLabel }}">{{ __('Contraseña actual') }}</label>
            <input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full {{ $bgInput }} {{ $textInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <label for="update_password_password" class="block font-medium text-sm {{ $textLabel }}">{{ __('Nueva contraseña') }}</label>
            <input id="update_password_password" name="password" type="password" class="mt-1 block w-full {{ $bgInput }} {{ $textInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <label for="update_password_password_confirmation" class="block font-medium text-sm {{ $textLabel }}">{{ __('Confirmar contraseña') }}</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full {{ $bgInput }} {{ $textInput }} rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Guardar') }}</x-primary-button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm {{ $textSuccess }}"
                >{{ __('Guardado.') }}</p>
            @endif
        </div>
    </form>
</section>
