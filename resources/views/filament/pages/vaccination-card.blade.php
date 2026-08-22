<x-filament-panels::page>
    <style>
        @media print {
            @page {
                margin: 12mm;
            }

            html,
            body {
                background: #fff !important;
                height: auto !important;
                overflow: visible !important;
            }

            body * {
                visibility: hidden !important;
            }

            #vaccination-card-print,
            #vaccination-card-print * {
                visibility: visible !important;
            }

            #vaccination-card-print {
                position: absolute !important;
                inset: 0 auto auto 0 !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                border: 0 !important;
                box-shadow: none !important;
                background: #fff !important;
                color: #111827 !important;
                overflow: visible !important;
            }

            .fi-sidebar,
            .fi-sidebar-close-overlay,
            .fi-topbar,
            .fi-header,
            .fi-modal,
            .fi-notifications,
            .vaccination-card-print-controls {
                display: none !important;
            }
        }
    </style>

    <div class="space-y-6">
        <div class="vaccination-card-print-controls flex items-center justify-between gap-4">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Printable Ghana EPI vaccination card. Use your browser print dialog for a paper copy.
            </p>
            <x-filament::button
                color="gray"
                tag="button"
                type="button"
                x-on:click.prevent="window.print()"
            >
                Print
            </x-filament::button>
        </div>

        @php($patient = $this->patient())

        @if ($patient === null)
            <x-filament::section>
                <p class="text-sm text-gray-600 dark:text-gray-300">
                    Open this page from a child health record to print that child’s vaccination card.
                </p>
            </x-filament::section>
        @else
            <div
                id="vaccination-card-print"
                class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-900"
            >
                <div class="mb-6 grid gap-2 text-sm">
                    <div class="text-lg font-semibold text-gray-950 dark:text-white">
                        {{ $patient->full_name }}
                    </div>
                    <div class="text-gray-600 dark:text-gray-300">
                        MRN: {{ $patient->mrn ?? '—' }}
                    </div>
                    <div class="text-gray-600 dark:text-gray-300">
                        Date of birth: {{ optional($patient->date_of_birth)->toDateString() ?? '—' }}
                    </div>
                    <div class="text-gray-600 dark:text-gray-300">
                        Printed: {{ now()->toDateString() }}
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead>
                            <tr class="text-left text-gray-500 dark:text-gray-400">
                                <th class="py-2 pr-4 font-medium">Vaccine</th>
                                <th class="py-2 pr-4 font-medium">Dose</th>
                                <th class="py-2 pr-4 font-medium">Status</th>
                                <th class="py-2 pr-4 font-medium">Date</th>
                                <th class="py-2 font-medium">Batch / lot</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse ($this->records() as $record)
                                <tr class="text-gray-950 dark:text-white">
                                    <td class="py-2 pr-4">{{ $record->vaccine?->name ?? '—' }}</td>
                                    <td class="py-2 pr-4">{{ $record->dose_sequence }}</td>
                                    <td class="py-2 pr-4">{{ $record->status?->getLabel() ?? $record->status }}</td>
                                    <td class="py-2 pr-4">{{ optional($record->administered_date)->toDateString() ?? '—' }}</td>
                                    <td class="py-2">{{ $record->batch_lot ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-4 text-gray-500 dark:text-gray-400">
                                        No immunization records for this child yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
