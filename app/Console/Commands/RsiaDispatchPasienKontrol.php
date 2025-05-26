<?php

namespace App\Console\Commands;

use App\Jobs\SendWhatsApp;
use App\Models\RegPeriksa;
use Illuminate\Support\Carbon;
use Illuminate\Console\Command;

class RsiaDispatchPasienKontrol extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rsia:dispatch-pasien-kontrol {--besok : Ambil pasien kontrol untuk besok, (default: hari ini)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ambil pasien kontrol dari RSIA dan kirimkan pesan WhatsApp';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $targetDate = $this->option('besok')
            ? Carbon::tomorrow()
            : Carbon::today();

        $tanggalLabel = $targetDate->translatedFormat('l, d F Y');

        $baseDelay = Carbon::today()->setTimeFromTimeString(
            $this->option('besok') ? '03:30:00' : '09:00:00'
        );

        $records = RegPeriksa::with(['dokter', 'poli', 'pasien'])
            ->whereDate('tgl_registrasi', $targetDate->toDateString())
            ->get();

        if ($records->isEmpty()) {
            $this->info("Tidak ada pasien kontrol untuk tanggal {$tanggalLabel}");
            return;
        }

        $this->info("Mengirim reminder untuk tanggal {$tanggalLabel} ({$records->count()} pasien)");

        foreach ($records as $index => $record) {
            $receiver = $record->pasien->no_tlp ?? null;

            $nmPasien = $record?->pasien?->nm_pasien ?? 'Pasien';
            $registrasi = Carbon::parse($record->tgl_registrasi)->translatedFormat('l, d F Y');
            $poli = $record?->poli?->nm_poli ?? 'Poliklinik';
            $dokter = $record?->dokter?->nm_dokter ?? 'Dokter';

            $jam = \App\Models\JadwalPoliklinik::where('kd_dokter', $record->kd_dokter)
                ->where('kd_poli', $record->kd_poli)
                ->where('hari_kerja', $targetDate->translatedFormat('l'))
                ->first();

            $msg  = "Halo, {$nmPasien}! 👋" . "<br />";
            $msg .="Ini adalah pengingat untuk jadwal kontrol kesehatan Anda yang telah dijadwalkan pada:" . "<br /><br />";

            $msg .="🗓 *Tanggal* : {$registrasi}" . "<br />";
            $msg .="⌛ *Waktu* : " . Carbon::parse($jam->jam_mulai)->translatedFormat('H:i') . " WIB - Selesai" . "<br />";
            $msg .="🏥 *Poliklinik* : {$poli}" . "<br />";
            $msg .="🩺 *Dokter* : {$dokter}" . "<br /><br />";

            $msg .="Pastikan untuk hadir tepat waktu dan membawa dokumen yang diperlukan. Jika ada perubahan atau Anda tidak dapat hadir, mohon beri tahu kami secepatnya." . "<br /><br />";
            $msg .="Terima kasih, semoga sehat selalu! 😊";

            SendWhatsApp::dispatch($msg, $receiver, config('waha.sessions.pendaftaran'))
                ->delay($baseDelay)
                ->onQueue('whatsapp');

            $baseDelay->addSeconds(rand(7, 28));
        }

        $this->info("Reminder berhasil dijadwalkan.");
    }
}
