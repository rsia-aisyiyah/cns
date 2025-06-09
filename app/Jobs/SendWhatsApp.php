<?php

namespace App\Jobs;

use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendWhatsApp implements ShouldQueue
{
    use Queueable;

    /**
     * Message to be sent.
     *
     * @var string
     */
    protected $message;

    /**
     * Phone number to send the message to.
     * 
     * @var string
     */
    protected $receiver;

    /**
     * The name of the queue the job should be sent to.
     *
     * @var string
     */
    protected $sessionName;

    /**
     * Create a new job instance.
     */
    public function __construct(string $message, string $receiver, string $sessionName)
    {
        $this->message = $message;
        $this->receiver = $receiver;
        $this->sessionName = $sessionName;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $whatsappBaseApiUrl = env('API_WHATSAPP_URL');

        // Refine phone number dulu
        $refinedPhoneNumber = $this->refinePhoneNumber($this->receiver);

        if (!$refinedPhoneNumber) {
            Log::channel('whatsapp')->error("Invalid phone number format: {$this->receiver}", [
                'receiver' => $this->receiver,
            ]);
            throw new \Exception("Invalid phone number format: {$this->receiver}");
        }

        $receiverPhoneNumber = "{$refinedPhoneNumber}@c.us";

        // Kirim pesan WA
        $response = \Illuminate\Support\Facades\Http::post("$whatsappBaseApiUrl/api/sendText", [
            "chatId"                 => $receiverPhoneNumber,
            "text"                   => $this->htmlToWhatsAppText($this->message),
            "session"                => $this->sessionName,
            "linkPreview"            => true,
            "linkPreviewHighQuality" => true,
            "reply_to"               => null,
        ]);

        if ($response->successful()) {
            Log::channel('whatsapp')->info("Whatsapp sent to $refinedPhoneNumber", [
                'receiver' => $refinedPhoneNumber,
                'status'   => $response->status(),
                'response' => $response->body(),
            ]);
        } else {
            Log::channel('whatsapp')->error("Failed to send whatsapp to $refinedPhoneNumber", [
                'receiver' => $refinedPhoneNumber,
                'response' => $response->body(),
                'status'   => $response->status(),
            ]);
            throw new \Exception("Failed to send whatsapp to $refinedPhoneNumber");
        }
    }

    public function htmlToWhatsAppText($html)
    {
        // hapus spasi lebih dari 1
        $text = preg_replace('/\s+/', ' ', $html);

        // replace <br> with \n, but remove extra newlines to avoid excessive spacing
        $text = preg_replace('/<br\s*\/?>/i', "\n", $text);

        // replace <b> and <strong> with *
        $text = preg_replace('/<(b|strong)([^>]*)>(.*?)<\/\1>/', '*$3*', $text);

        // replace <i> with _
        $text = preg_replace('/<i>(.*?)<\/i>/', '_$1_', $text);

        // replace <s> with ~
        $text = preg_replace('/<s>(.*?)<\/s>/', '~$1~', $text);

        // remove all other HTML tags but keep text formatting
        $text = strip_tags($text);

        // clean up any extra newline and spaces (optional, depending on how clean you want the output)
        // $text = preg_replace('/\n+/', "\n", $text); // Remove multiple newlines

        // trim any leading/trailing spaces
        $text = trim($text);


        return $text;
    }


    protected function refinePhoneNumber($phoneNumber)
    {
        // Hapus semua karakter non-digit
        $phoneNumber = preg_replace('/\D/', '', $phoneNumber);

        // Cek dan ubah awalan
        if (strpos($phoneNumber, '0') === 0) {
            // Jika diawali 0, ubah menjadi 62
            $phoneNumber = '62' . substr($phoneNumber, 1);
        } elseif (strpos($phoneNumber, '62') === 0) {
            // Jika sudah diawali 62, biarkan
        } else {
            // Jika awalan selain itu (tidak valid)
            return null;
        }

        // Validasi panjang setelah 62
        $withoutCode = substr($phoneNumber, 2);

        if (strlen($withoutCode) >= 9 && strlen($withoutCode) <= 12) {
            return $phoneNumber;
        }

        // Jika tidak memenuhi syarat
        return null;
    }
}
