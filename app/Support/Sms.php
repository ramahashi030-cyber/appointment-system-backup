<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * OTP SMS delivery — same gateway the legacy QALINGA1 verify.php used
 * (https://qmmc.myresultonline.com/api/send/{contact}/{message}).
 */
class Sms
{
    /**
     * Send a 6-digit OTP to a contact number.
     *
     * Failures are logged instead of thrown so the registration flow can return
     * a controlled response to the patient.
     */
    public static function sendOtp(string $contact, string $otp): bool
    {
        $window = (int) config('services.otp.window', 60);

        return static::send(
            $contact,
            sprintf('Your OTP code is %s. Valid for %d seconds.', $otp, $window)
        );
    }

    /**
     * Raw gateway call. Returns true when the gateway accepted the message.
     */
    public static function send(string $contact, string $message): bool
    {
        $contact = trim($contact);

        if ($contact === '') {
            return false;
        }

        $base = rtrim((string) config('services.sms.api', 'https://qmmc.myresultonline.com/api/send'), '/');
        $url = $base.'/'.$contact.'/'.rawurlencode($message);

        try {
            $response = Http::withoutVerifying()->timeout(10)->get($url);

            if (! $response->successful()) {
                Log::warning('SMS gateway rejected the message', [
                    'contact' => $contact,
                    'status' => $response->status(),
                    'response' => substr((string) $response->body(), 0, 300),
                ]);

                return false;
            }

            return true;
        } catch (Throwable $e) {
            Log::warning('SMS gateway unreachable', [
                'contact' => $contact,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
