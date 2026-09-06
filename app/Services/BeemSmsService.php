<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BeemSmsService
{
    /**
     * Send an SMS through Beem Africa.
     * Returns true on success. When SMS is disabled (no credentials / local dev)
     * the message is logged instead so OTP flows still work end-to-end.
     */
    public function send(string $phone, string $message): bool
    {
        $cfg = config('services.beem');
        $recipient = $this->normalise($phone);

        if (! $cfg['enabled'] || empty($cfg['key']) || empty($cfg['secret'])) {
            Log::channel('stack')->info("[SMS mock] to {$recipient}: {$message}");

            return true;
        }

        try {
            $response = Http::withBasicAuth($cfg['key'], $cfg['secret'])
                ->acceptJson()
                ->timeout(15)
                ->post($cfg['sms_url'], [
                    'source_addr' => $cfg['sender_id'],
                    'schedule_time' => '',
                    'encoding' => 0,
                    'message' => $message,
                    'recipients' => [
                        ['recipient_id' => 1, 'dest_addr' => $recipient],
                    ],
                ]);

            if ($response->successful() && ($response->json('successful') ?? false)) {
                return true;
            }

            Log::warning('Beem SMS failed', ['status' => $response->status(), 'body' => $response->body()]);

            return false;
        } catch (\Throwable $e) {
            Log::error('Beem SMS exception', ['message' => $e->getMessage()]);

            return false;
        }
    }

    private function normalise(string $phone): string
    {
        $p = preg_replace('/\D+/', '', $phone);
        if (str_starts_with($p, '0')) {
            $p = '255'.substr($p, 1);
        }
        if (! str_starts_with($p, '255') && strlen($p) === 9) {
            $p = '255'.$p;
        }

        return $p;
    }
}
