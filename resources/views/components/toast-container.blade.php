{{-- Contenedor para notificaciones Toast --}}
<div 
    x-data="toastContainer()"
    x-show="toasts.length > 0"
    class="fixed top-4 right-4 z-50 w-full max-w-sm space-y-4 pointer-events-none"
    style="max-height: calc(100vh - 2rem); overflow-y: auto;"
>
    <template x-for="(toast, index) in toasts" :key="toast.id">
        <div 
            x-data="toastNotification(toast)"
            x-show="visible"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform translate-x-full"
            x-transition:enter-end="opacity-100 transform translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform translate-x-0"
            x-transition:leave-end="opacity-0 transform translate-x-full"
            class="pointer-events-auto relative flex items-center w-full max-w-sm p-4 mb-4 text-gray-500 bg-white rounded-lg shadow-lg dark:text-gray-400 dark:bg-gray-800 border border-gray-200 dark:border-gray-700"
            :class="{
                'border-green-500 dark:border-green-400': type === 'success',
                'border-red-500 dark:border-red-400': type === 'error',
                'border-yellow-500 dark:border-yellow-400': type === 'warning',
                'border-blue-500 dark:border-blue-400': type === 'info'
            }"
            role="alert"
        >
            {{-- Icono según tipo --}}
            <div class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 rounded-lg"
                 :class="{
                    'text-green-500 bg-green-100 dark:bg-green-800 dark:text-green-200': type === 'success',
                    'text-red-500 bg-red-100 dark:bg-red-800 dark:text-red-200': type === 'error',
                    'text-yellow-500 bg-yellow-100 dark:bg-yellow-800 dark:text-yellow-200': type === 'warning',
                    'text-blue-500 bg-blue-100 dark:bg-blue-800 dark:text-blue-200': type === 'info'
                 }">
                <template x-if="type === 'success'">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                </template>
                <template x-if="type === 'error'">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                </template>
                <template x-if="type === 'warning'">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                </template>
                <template x-if="type === 'info'">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                </template>
            </div>

            {{-- Mensaje --}}
            <div class="ml-3 text-sm font-normal flex-1" x-text="message"></div>

            {{-- Barra de progreso para auto-cierre --}}
            <template x-if="autoClose && duration > 0">
                <div class="absolute bottom-0 left-0 right-0 h-1 bg-gray-200 dark:bg-gray-700 rounded-b-lg overflow-hidden">
                    <div 
                        class="h-full transition-all ease-linear rounded-b-lg"
                        :class="{
                            'bg-green-500': type === 'success',
                            'bg-red-500': type === 'error',
                            'bg-yellow-500': type === 'warning',
                            'bg-blue-500': type === 'info'
                        }"
                        :style="`width: ${progress}%`"
                    ></div>
                </div>
            </template>

            {{-- Botón de cierre --}}
            <button 
                type="button"
                @click="close()"
                class="ml-auto -mx-1.5 -my-1.5 text-gray-400 hover:text-gray-900 rounded-lg focus:ring-2 focus:ring-gray-300 p-1.5 hover:bg-gray-100 dark:hover:bg-gray-700 dark:hover:text-white dark:text-gray-500"
                aria-label="Cerrar"
            >
                <span class="sr-only">Cerrar</span>
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>
            </button>
        </div>
    </template>
</div>
