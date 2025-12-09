<?php

namespace App\Services;

use App\Models\UserOtp;
use Carbon\Carbon;

class OtpService
{
    /**
     * Generate a 4-digit OTP
     *
     * @return string
     */
    public function generateOtp(): string
    {
        return str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Create and store OTP for a phone number
     *
     * @param string $phone
     * @return string The generated OTP
     */
    public function createOtp(string $phone): string
    {
        // Delete any existing OTPs for this phone
        UserOtp::where('phone', $phone)->delete();

        // Generate new OTP
        $otp = $this->generateOtp();

        // Store OTP (expires in 5 minutes)
        UserOtp::create([
            'phone' => $phone,
            'otp' => $otp,
            'expires_at' => Carbon::now()->addMinutes(5),
        ]);

        return $otp;
    }

    /**
     * Verify OTP for a phone number
     *
     * @param string $phone
     * @param string $otp
     * @return bool
     */
    public function verifyOtp(string $phone, string $otp): bool
    {
        $userOtp = UserOtp::where('phone', $phone)
            ->where('otp', $otp)
            ->where('expires_at', '>', Carbon::now())
            ->first();

        if (!$userOtp) {
            return false;
        }

        // Delete the OTP after successful verification
        $userOtp->delete();

        return true;
    }

    /**
     * Delete expired OTPs
     *
     * @return int Number of deleted records
     */
    public function deleteExpiredOtps(): int
    {
        return UserOtp::where('expires_at', '<', Carbon::now())->delete();
    }
}
