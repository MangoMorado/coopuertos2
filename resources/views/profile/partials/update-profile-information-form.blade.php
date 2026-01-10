
<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Información del perfil') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Actualiza la información de tu cuenta y tu correo electrónico.') }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <label for="name" class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('Nombre') }}</label>
            <input id="name" name="name" type="text" class="mt-1 block w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <label for="email" class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('Correo electrónico') }}</label>
            <input id="email" name="email" type="email" class="mt-1 block w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="{{ old('email', $user->email) }}" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="text-sm mt-2 text-gray-700 dark:text-gray-300">
                        {{ __('Tu correo electrónico no está verificado.') }}

                        <button form="send-verification" class="underline text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-gray-800 focus:ring-indigo-500">
                            {{ __('Haz clic aquí para reenviar el correo de verificación.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 font-medium text-sm text-green-700 dark:text-green-400">
                            {{ __('Se envió un nuevo enlace de verificación a tu correo electrónico.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div>
            <label for="theme" class="block font-medium text-sm text-gray-700 dark:text-gray-300">{{ __('Tema') }}</label>
            <select id="theme" name="theme" class="mt-1 block w-full bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-gray-100 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500" 
                    x-model="$store.theme.current"
                    x-on:change="$store.theme.setTheme($event.target.value)">
                <option value="light" {{ old('theme', $user->theme ?? 'light') === 'light' ? 'selected' : '' }}>
                    {{ __('Claro') }}
                </option>
                <option value="dark" {{ old('theme', $user->theme ?? 'light') === 'dark' ? 'selected' : '' }}>
                    {{ __('Oscuro') }}
                </option>
            </select>
            <x-input-error class="mt-2" :messages="$errors->get('theme')" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('Guardar') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-gray-600 dark:text-gray-400"
                >{{ __('Guardado.') }}</p>
            @endif
        </div>
    </form>
</section>
