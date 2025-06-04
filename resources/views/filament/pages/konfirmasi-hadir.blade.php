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
            {{-- Session Name --}}
            <x-filament::section class="text-pretty">
                <p>WhatsApp Session yang digunakan : </p>
                <div class="font-semibold text-emerald-500">
                    {{ $this->getWahaSesstionName() }} ( {{ config('waha.sessions')[$this->getWahaSesstionName()]['id'] }} )
                </div>
            </x-filament::section>
            
            {{-- Pesan Notifikasi Preview --}}
            <x-filament::section class="text-pretty">
                {!! $this->generatePreviewMessage() !!}
            </x-filament::section>
        </div>
    </div>

</x-filament-panels::page>
