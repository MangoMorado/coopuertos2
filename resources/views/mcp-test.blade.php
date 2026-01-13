<x-app-layout>
    <div class="py-4 sm:py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-100 mb-6">
                    🧪 Prueba del Servidor MCP
                </h1>

                <div class="mb-6">
                    <label for="mcp-url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        URL del Servidor
                    </label>
                    <div class="flex gap-2">
                        <input
                            type="text"
                            id="mcp-url"
                            value="{{ url('/') }}"
                            class="flex-1 rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="http://localhost:8000 o https://tu-dominio.com"
                        />
                        <button
                            onclick="runTests()"
                            class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                        >
                            Ejecutar Pruebas
                        </button>
                    </div>
                </div>

                <div id="loading" class="hidden mb-6">
                    <div class="flex items-center gap-2 text-indigo-600 dark:text-indigo-400">
                        <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Ejecutando pruebas...</span>
                    </div>
                </div>

                <div id="results" class="hidden">
                    <div class="mb-4 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg">
                        <p class="text-sm text-gray-600 dark:text-gray-400">
                            <strong>Endpoint:</strong> <span id="endpoint-url" class="font-mono"></span>
                        </p>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            <strong>Fecha:</strong> <span id="test-timestamp"></span>
                        </p>
                    </div>

                    <div id="test-results" class="space-y-4"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function runTests() {
            const url = document.getElementById('mcp-url').value;
            const loading = document.getElementById('loading');
            const results = document.getElementById('results');
            const testResults = document.getElementById('test-results');

            loading.classList.remove('hidden');
            results.classList.add('hidden');
            testResults.innerHTML = '';

            try {
                const response = await fetch('{{ route("mcp.test.run") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({ url: url }),
                });

                const data = await response.json();

                document.getElementById('endpoint-url').textContent = data.endpoint;
                document.getElementById('test-timestamp').textContent = data.timestamp;

                let html = '';
                for (const [key, test] of Object.entries(data.tests)) {
                    const statusIcon = test.success ? '✅' : '❌';
                    const statusColor = test.success
                        ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800'
                        : 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800';
                    const textColor = test.success
                        ? 'text-green-800 dark:text-green-200'
                        : 'text-red-800 dark:text-red-200';

                    html += `
                        <div class="border rounded-lg p-4 ${statusColor}">
                            <div class="flex items-center justify-between mb-2">
                                <h3 class="font-semibold ${textColor}">
                                    ${statusIcon} ${test.name}
                                </h3>
                                ${test.count !== undefined ? `<span class="text-sm ${textColor}">${test.count} items</span>` : ''}
                            </div>
                            <p class="text-sm ${textColor} mb-2">${test.message}</p>
                            ${test.data ? `
                                <details class="mt-2">
                                    <summary class="text-xs cursor-pointer ${textColor} hover:underline">Ver respuesta completa</summary>
                                    <pre class="mt-2 p-2 bg-gray-100 dark:bg-gray-800 rounded text-xs overflow-auto max-h-64">${JSON.stringify(test.data, null, 2)}</pre>
                                </details>
                            ` : ''}
                        </div>
                    `;
                }

                testResults.innerHTML = html;
                results.classList.remove('hidden');
            } catch (error) {
                testResults.innerHTML = `
                    <div class="border border-red-200 dark:border-red-800 rounded-lg p-4 bg-red-50 dark:bg-red-900/20">
                        <p class="text-red-800 dark:text-red-200 font-semibold">Error al ejecutar pruebas</p>
                        <p class="text-sm text-red-600 dark:text-red-300 mt-1">${error.message}</p>
                    </div>
                `;
                results.classList.remove('hidden');
            } finally {
                loading.classList.add('hidden');
            }
        }
    </script>
</x-app-layout>
