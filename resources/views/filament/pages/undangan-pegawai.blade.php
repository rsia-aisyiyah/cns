<x-filament-panels::page>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Filter Form --}}
    {{ $this->filterForm }}

    @if ($this->undangan)
        <x-filament::section class="text-pretty">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <div class="space-y-1.5">
                    <p class="leading-none font-semibold text-gray-900 dark:text-gray-50">Perihal</p>
                    <p class="leading-none text-gray-700 dark:text-gray-200">{{ $this->undangan->perihal }}</p>
                </div>
                <div class="space-y-1.5">
                    <p class="leading-none font-semibold text-gray-900 dark:text-gray-50">Lokasi</p>
                    <p class="leading-none text-gray-700 dark:text-gray-200">{{ $this->undangan->lokasi }}</p>
                </div>
                <div class="space-y-1.5">
                    <p class="leading-none font-semibold text-gray-900 dark:text-gray-50">Tanggal</p>
                    <p class="leading-none text-gray-700 dark:text-gray-200">{{ \Carbon\Carbon::parse($this->undangan->tanggal)->translatedFormat('l, d M Y H:i') }}</p>
                </div>
                <div class="space-y-1.5">
                    <p class="leading-none font-semibold text-gray-900 dark:text-gray-50">Status</p>
                    <div class="flex">
                        <x-filament::badge :color="match ($this->undangan->status) {
                            'pengajuan' => 'warning',
                            'disetujui' => 'success',
                            'ditolak' => 'danger',
                            'batal' => 'secondary',
                            default => 'primary',
                        }">
                            {{ $this->undangan->status }}
                        </x-filament::badge>
                    </div>
                </div>
            </div>
        </x-filament::section>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {{-- Table --}}
        <div class="lg:col-span-3">
            {{ $this->table }}
        </div>

        {{-- Queue Form --}}
        <div class="lg:col-span-1 space-y-6">
            {{-- Queue Form --}}
            {{ $this->queueForm }}

            {{-- Pesan Notifikasi Preview --}}
            <x-filament::section class="text-pretty">
                {!! $this->generatePreviewMessage() !!}
            </x-filament::section>

            <x-filament::button
                wire:click="notify"
                color="primary"
                class="w-full"
                icon="heroicon-o-paper-airplane"
                :disabled="!$this->undangan"
            >
                Kirim Notifikasi
            </x-filament::button>
        </div>
    </div>

</x-filament-panels::page>
