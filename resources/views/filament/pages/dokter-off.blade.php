<x-filament-panels::page>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Filter Form --}}
    {{ $this->filterForm }}

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {{-- Table --}}
        <div class="lg:col-span-3">
            {{ $this->table }}
        </div>

        <div class="lg:col-span-1 space-y-6">
            {{-- Queue Form --}}
            {{ $this->queueForm }}

            {{-- Pesan Notifikasi Preview --}}
            <x-filament::section class="text-pretty">
                {!! $this->generatePreviewMessage() !!}
            </x-filament::section>
        </div>
    </div>
</x-filament-panels::page>
