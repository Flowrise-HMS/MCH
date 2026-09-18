<x-filament-panels::page>
    <div class="space-y-6">
        @if ($mode === 'home' || $mode === 'register')
            <div class="flex flex-col gap-3 sm:flex-row sm:items-start">
                <div class="relative flex-1">
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="search"
                            wire:model.live.debounce.300ms="searchTerm"
                            placeholder="Search patients by name, MRN, or phone..."
                        />
                    </x-filament::input.wrapper>

                    @if (strlen($searchTerm) >= 2 && count($searchResults) > 0)
                        <div class="absolute z-50 mt-1 w-full rounded-xl border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-900">
                            @foreach ($searchResults as $result)
                                <button
                                    type="button"
                                    wire:click="selectPatient('{{ $result['id'] }}')"
                                    class="block w-full border-b border-gray-100 px-4 py-3 text-left last:border-0 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800"
                                >
                                    <div class="font-medium text-gray-950 dark:text-white">{{ $result['full_name'] }}</div>
                                    <div class="text-sm text-gray-500">MRN: {{ $result['mrn'] ?? '—' }}</div>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="flex gap-2">
                    <x-filament::button color="gray" wire:click="startRegistration('mother')">Register mother</x-filament::button>
                    <x-filament::button color="gray" wire:click="startRegistration('child')">Register child</x-filament::button>
                </div>
            </div>

            @if ($mode === 'register')
                <x-filament::section>
                    <x-slot name="heading">Register {{ $registerKind }}</x-slot>
                    <div wire:key="mch-register-{{ $registerKind }}">
                        {{ $this->registerForm }}
                    </div>
                    <div class="mt-4 flex gap-2">
                        <x-filament::button wire:click="submitRegistration">Save</x-filament::button>
                        <x-filament::button color="gray" wire:click="cancelRegistration">Cancel</x-filament::button>
                    </div>
                </x-filament::section>
            @endif

            <div class="grid gap-4 lg:grid-cols-3">
                <x-filament::section>
                    <x-slot name="heading">ANC today ({{ $this->ancToday()->count() }})</x-slot>
                    <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($this->ancToday() as $patient)
                            @php($pregnancy = $patient->activePregnancyEpisode)
                            <li>
                                <button type="button" wire:click="selectPatient('{{ $patient->id }}')" class="w-full px-1 py-2 text-left hover:text-primary-600">
                                    {{ $patient->full_name }}
                                    @if ($pregnancy)
                                        <span class="block text-xs text-gray-500">
                                            EDD {{ optional($pregnancy->edd)->toDateString() ?? '—' }}
                                            @if ($ga = $this->gestationalAgeLabel($pregnancy))
                                                · GA {{ $ga }}
                                            @endif
                                            @if ($pregnancy->risk_level === \Modules\MCH\Enums\RiskLevel::HIGH)
                                                · <span class="text-danger-600">high risk</span>
                                            @endif
                                        </span>
                                    @endif
                                </button>
                            </li>
                        @empty
                            <li class="text-sm text-gray-500">No ANC returns or open antenatal encounters today.</li>
                        @endforelse
                    </ul>
                </x-filament::section>

                <x-filament::section>
                    <x-slot name="heading">CWC today ({{ $this->cwcToday()->count() }})</x-slot>
                    <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($this->cwcToday() as $patient)
                            <li>
                                <button type="button" wire:click="selectPatient('{{ $patient->id }}')" class="w-full px-1 py-2 text-left hover:text-primary-600">
                                    {{ $patient->full_name }}
                                </button>
                            </li>
                        @empty
                            <li class="text-sm text-gray-500">No open child welfare encounters today.</li>
                        @endforelse
                    </ul>
                </x-filament::section>

                <x-filament::section>
                    <x-slot name="heading">EPI due / overdue ({{ $this->epiDue()->count() }})</x-slot>
                    <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($this->epiDue() as $row)
                            <li>
                                <button type="button" wire:click="selectPatient('{{ $row['patient']->id }}')" class="w-full px-1 py-2 text-left hover:text-primary-600">
                                    {{ $row['patient']->full_name }}
                                    <span class="text-xs {{ $row['overdue'] ? 'text-danger-600' : 'text-gray-500' }}">
                                        {{ $row['overdue'] ? 'overdue' : 'due' }} · {{ $row['scheduled_count'] }}
                                    </span>
                                </button>
                            </li>
                        @empty
                            <li class="text-sm text-gray-500">No scheduled dues. Open a child and generate EPI dues.</li>
                        @endforelse
                    </ul>
                </x-filament::section>
            </div>

            <div class="grid gap-4 lg:grid-cols-4">
                <x-filament::section>
                    <x-slot name="heading">EDD within 14 days ({{ $this->eddDueSoon()->count() }})</x-slot>
                    <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($this->eddDueSoon() as $episode)
                            <li>
                                <button type="button" wire:click="selectPatient('{{ $episode->patient_id }}')" class="w-full px-1 py-2 text-left hover:text-primary-600">
                                    {{ $episode->patient?->full_name ?? 'Patient' }}
                                    <span class="block text-xs text-gray-500">EDD {{ optional($episode->edd)->toDateString() }}</span>
                                </button>
                            </li>
                        @empty
                            <li class="text-sm text-gray-500">No deliveries expected in the next two weeks.</li>
                        @endforelse
                    </ul>
                </x-filament::section>

                <x-filament::section>
                    <x-slot name="heading">High-risk pregnancies</x-slot>
                    <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($this->highRisk() as $episode)
                            <li>
                                <button type="button" wire:click="selectPatient('{{ $episode->patient_id }}')" class="w-full px-1 py-2 text-left hover:text-primary-600">
                                    {{ $episode->patient?->full_name ?? 'Patient' }}
                                    <span class="text-xs text-danger-600">high risk</span>
                                </button>
                            </li>
                        @empty
                            <li class="text-sm text-gray-500">None listed.</li>
                        @endforelse
                    </ul>
                </x-filament::section>

                <x-filament::section>
                    <x-slot name="heading">Books issued today</x-slot>
                    <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($this->booksToday() as $book)
                            <li class="px-1 py-2 text-sm">{{ $book->unit }} · {{ $book->serial_number }}</li>
                        @empty
                            <li class="text-sm text-gray-500">No books issued today.</li>
                        @endforelse
                    </ul>
                </x-filament::section>

                <x-filament::section>
                    <x-slot name="heading">Recent</x-slot>
                    <ul class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($this->recentPatients() as $patient)
                            <li>
                                <button type="button" wire:click="selectPatient('{{ $patient->id }}')" class="w-full px-1 py-2 text-left hover:text-primary-600">
                                    {{ $patient->full_name }}
                                </button>
                            </li>
                        @empty
                            <li class="text-sm text-gray-500">No recent patients.</li>
                        @endforelse
                    </ul>
                </x-filament::section>
            </div>
        @endif

        @if ($mode === 'patient' && $currentPatient)
            @if ($vitalsOverview = $this->vitalsOverviewWidgetClass())
                @livewire($vitalsOverview, [
                    'patientId' => $currentPatient->id,
                    'encounterId' => $currentEncounter?->id,
                ], key('mch-vitals-overview-'.$currentPatient->id))
            @endif

            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div class="text-lg font-semibold text-gray-950 dark:text-white">{{ $currentPatient->full_name }}</div>
                        <div class="text-sm text-gray-500">
                            MRN {{ $currentPatient->mrn ?? '—' }}
                            · DOB {{ optional($currentPatient->date_of_birth)->toDateString() ?? '—' }}
                            ·
                            @if ($context['kind'] === 'mother')
                                Pregnant
                                @if ($context['pregnancy'])
                                    · EDD {{ optional($context['pregnancy']->edd)->toDateString() ?? '—' }}
                                    @if ($ga = $this->gestationalAgeToday())
                                        · GA {{ $ga }}
                                    @endif
                                    · {{ $context['pregnancy']->risk_level?->getLabel() }}
                                @endif
                            @elseif ($context['kind'] === 'child')
                                Child (CWC)
                            @else
                                No MCH registry
                            @endif
                        </div>
                        @if ($currentEncounter)
                            <div class="mt-1 text-xs text-primary-600">
                                Encounter {{ $currentEncounter->encounter_number ?? $currentEncounter->id }} · {{ $currentEncounter->type?->value }}
                            </div>
                        @endif
                    </div>
                    <div class="flex flex-wrap gap-2">
                        {{-- Vaccination card and the other patient actions live in the page header. --}}
                        <x-filament::button color="gray" wire:click="clearPatient">Clear</x-filament::button>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach ($this->availableTabs() as $tab)
                        <x-filament::button
                            size="sm"
                            :color="$activeTab === $tab ? 'primary' : 'gray'"
                            wire:click="setTab('{{ $tab }}')"
                        >
                            {{ str_replace('-', ' ', ucfirst($tab)) }}
                        </x-filament::button>
                    @endforeach
                </div>
            </div>

            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                @if ($activeTab === 'overview')
                    @if ($context['kind'] === 'unknown')
                        <p class="text-sm text-gray-600 dark:text-gray-300">Register a pregnancy episode or CWC record to unlock visit tabs.</p>
                        <div class="mt-3 flex gap-2">
                            <x-filament::button wire:click="startRegistration('mother')">Register mother</x-filament::button>
                            <x-filament::button wire:click="startRegistration('child')">Register child</x-filament::button>
                        </div>
                    @elseif ($context['kind'] === 'mother')
                        <p class="text-sm">Active pregnancy · risk {{ $context['pregnancy']?->risk_level?->getLabel() ?? '—' }}</p>
                        <div class="mt-3">
                            {{ $this->recordOutcomeAction }}
                        </div>
                    @else
                        <p class="text-sm">CWC enrolled · generate EPI dues from the Immunizations tab when needed.</p>
                    @endif
                @elseif ($activeTab === 'encounter')
                    <x-filament::button wire:click="startVisit">Start / open today’s visit encounter</x-filament::button>
                    @if ($currentEncounter)
                        <p class="mt-3 text-sm text-gray-600">Using encounter {{ $currentEncounter->encounter_number ?? $currentEncounter->id }}.</p>
                    @endif
                @elseif ($activeTab === 'anc-visit')
                    <p class="mb-3 text-xs text-gray-500">BP and weight are saved to the Clinical vitals record for this encounter.</p>
                    {{ $this->ancVisitForm }}
                    <div class="mt-4">
                        <x-filament::button wire:click="saveAncVisit">Save ANC visit</x-filament::button>
                    </div>
                @elseif ($activeTab === 'cwc-visit')
                    {{ $this->cwcVisitForm }}
                    <div class="mt-4">
                        <x-filament::button wire:click="saveCwcVisit">Save CWC visit</x-filament::button>
                    </div>
                @elseif ($activeTab === 'immunizations')
                    <div class="mb-4 flex flex-wrap gap-2">
                        <x-filament::button wire:click="generateEpiDues">
                            {{ $context['kind'] === 'mother' ? 'Generate TT dues' : 'Generate dues' }}
                        </x-filament::button>
                        <x-filament::input.wrapper>
                            <x-filament::input type="text" wire:model="administerBatchLot" placeholder="Batch/lot for administer" />
                        </x-filament::input.wrapper>
                        <x-filament::input.wrapper>
                            <x-filament::input type="text" wire:model="declineReason" placeholder="Decline reason" />
                        </x-filament::input.wrapper>
                    </div>
                    <ul class="mb-4 divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach ($this->immunizationRecords() as $record)
                            <li class="flex flex-wrap items-center justify-between gap-2 py-2 text-sm">
                                <span>
                                    {{ $record->vaccine?->name }} · dose {{ $record->dose_sequence }} · {{ $record->status?->getLabel() }}
                                </span>
                                @if ($record->status === \Modules\MCH\Enums\ImmunizationStatus::SCHEDULED || $record->status === \Modules\MCH\Enums\ImmunizationStatus::DECLINED)
                                    <span class="flex gap-2">
                                        <x-filament::button size="sm" wire:click="administerDose('{{ $record->id }}')">Administer</x-filament::button>
                                        @if ($record->status === \Modules\MCH\Enums\ImmunizationStatus::SCHEDULED)
                                            <x-filament::button size="sm" color="warning" wire:click="declineDose('{{ $record->id }}')">Decline</x-filament::button>
                                        @endif
                                    </span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                    <p class="text-xs text-gray-500">Full immunization table appears below with other recorded data.</p>
                @elseif ($activeTab === 'growth')
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Growth measurements captured on CWC visits are listed in the Growth measurements table below.
                    </p>
                @elseif ($activeTab === 'books')
                    <div class="flex gap-2">
                        {{ $this->issueBookAction }}
                    </div>
                @elseif ($activeTab === 'vitals')
                    @if ($vitalsOverview = $this->vitalsOverviewWidgetClass())
                        <p class="mb-3 text-sm text-gray-600 dark:text-gray-300">
                            Same vitals as Clinical Workspace — readings taken in OPD or here share one record set. History table is below.
                        </p>
                    @else
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            Clinical module is unavailable, so vitals widgets cannot be shown.
                        </p>
                    @endif
                @elseif ($activeTab === 'history')
                    <p class="text-sm text-gray-600 dark:text-gray-300">
                        Visit, immunization, and growth records for this client are shown in the tables below.
                    </p>
                @endif
            </div>
        @endif
    </div>
</x-filament-panels::page>
