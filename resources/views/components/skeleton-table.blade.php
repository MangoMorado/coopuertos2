{{-- Skeleton Loader para Tablas --}}
<div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700">
                <tr>
                    @for($i = 0; $i < ($columns ?? 5); $i++)
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                            <div class="h-4 bg-gray-200 dark:bg-gray-600 rounded animate-skeleton-pulse" style="animation-delay: {{ $i * 0.1 }}s;"></div>
                        </th>
                    @endfor
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @for($row = 0; $row < ($rows ?? 5); $row++)
                    <tr>
                        @for($col = 0; $col < ($columns ?? 5); $col++)
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="h-4 bg-gray-200 dark:bg-gray-700 rounded animate-skeleton-pulse" style="animation-delay: {{ ($row + $col) * 0.05 }}s;"></div>
                            </td>
                        @endfor
                    </tr>
                @endfor
            </tbody>
        </table>
    </div>
</div>
