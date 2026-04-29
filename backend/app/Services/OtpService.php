<?php

namespace App\Services;

use App\Models\EmailOtp;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class OtpService
{
    /**
     * Send OTP to email
     */
    public function sendOtp(string $email, string $context = 'registration'): bool
    {
        // Delete any existing OTPs for this email and context (including soft‑deleted ones)
        EmailOtp::where('email', $email)
            ->where('context', $context)
            ->forceDelete();

        // Generate 6 digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $expirySeconds = (int) config('otp.expiry_seconds', 60);
        $expiryUnit = config('otp.expiry_unit', 'seconds');

        EmailOtp::create([
            'email' => $email,
            'otp' => $otp,
            'context' => $context,
            'expires_at' => Carbon::now()->addSeconds($expirySeconds),
        ]);
        // Ensure $context is displayed in capitalized words (e.g. "Forgot Password")
        $displayContext = ucwords(str_replace(['_', '-'], ' ', $context));

        // Build human-readable expiry text
        $expiryDisplay = $expiryUnit === 'minutes'
            ? ($expirySeconds / 60) . ' minute(s)'
            : $expirySeconds . ' second(s)';

        // Send OTP email using simple mail function and log the data
        // Log::info('Sending OTP email', ['email' => $email, 'otp' => $otp, 'context' => $displayContext]);
        $subject = "Your " . $displayContext . " OTP";
        $message = "Your " . $displayContext . " OTP is: {$otp}\n\nThis OTP will expire in {$expiryDisplay}.";
        Mail::raw($message, function ($mailMessage) use ($email, $subject) {
            $mailMessage->to($email)
                ->subject($subject);
        });

        return true;
    }

    /**
     * Verify OTP
     */
    public function verifyOtp(string $email, string $otp, string $context): bool
    {
        $otpRecord = EmailOtp::where('email', $email)
            ->where('otp', $otp)
            ->where('context', $context)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$otpRecord) {
            return false;
        }

        return true;
    }

    /**
     * Delete OTP
     */
    public function deleteOtp(string $email, string $context): void
    {
        EmailOtp::where('email', $email)
            ->where('context', $context)
            ->delete();
    }
}
