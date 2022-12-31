<?php

declare(strict_types=1);

/*
 * This file is part of the Extension "mfa_yubikey" for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Derhansen\MfaYubikey\Service;

/**
 * Common service for YubiKey OTP handling
 */
class YubikeyService
{
    public function isOtp(string $otp): bool
    {
        return strlen($otp) === 44 && $this->isModhexString($otp);
    }

    public function getIdFromOtp(string $otp): string
    {
        $yubikeyId = substr($otp, 0, 12);
        if ((!$this->isOtp($otp) && strlen($yubikeyId) !== 12) || !$this->isModhexString($yubikeyId)) {
            $yubikeyId = '';
        }
        return $yubikeyId;
    }

    public function isModhexString(string $modhex): bool
    {
        return strlen($modhex) > 0 && preg_match('/^[.bcdefghijklnprtuvxy]*$/', $modhex) === 1;
    }

    public function isInYubikeys(array $yubikeys, string $otp): bool
    {
        $yubikeyId = $this->getIdFromOtp($otp);
        $found = array_search($yubikeyId, array_column($yubikeys, 'yubikeyId'));
        return $found !== false;
    }

    public function updateYubikeyUsage(array $yubikeys, string $otp, int $timestamp): array
    {
        $result = [];
        $yubikeyId = $this->getIdFromOtp($otp);

        foreach ($yubikeys as $yubikey) {
            if ($yubikey['yubikeyId'] === $yubikeyId) {
                $yubikey['lastUsed'] = $timestamp;
            }
            $result[] = $yubikey;
        }

        return $result;
    }

    public function deleteFromYubikeys(array $yubikeys, string $yubikeyId): array
    {
        $result = [];

        foreach ($yubikeys as $yubikey) {
            if ($yubikey['yubikeyId'] === $yubikeyId) {
                continue;
            }
            $result[] = $yubikey;
        }

        return $result;
    }
}
