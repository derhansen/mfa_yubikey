<?php

namespace Derhansen\MfaYubikey\Tests\Unit;

/*
 * This file is part of the Extension "mfa_yubikey" for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Derhansen\MfaYubikey\Service\YubikeyService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class YubikeyServiceTest extends UnitTestCase
{
    protected YubikeyService $subject;

    protected function setUp(): void
    {
        $this->subject = new YubikeyService();
    }

    protected function tearDown(): void
    {
        unset($this->subject);
    }

    public static function isValidModhexStringDataProvider(): array
    {
        return [
            'empty string' => [
                '',
                false,
            ],
            'invalid chars' => [
                'cbdefghijxs',
                false,
            ],
            'valid chars' => [
                'cbdefghijklnrtuv',
                true,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider isValidModhexStringDataProvider
     */
    public function isValidModhexStringReturnsExpectedResults(string $testString, bool $expected): void
    {
        $result = $this->subject->isModhexString($testString);
        self::assertSame($expected, $result);
    }

    public static function getYubikeyIdFromOtpDataProvider(): array
    {
        return [
            'empty string' => [
                '',
                '',
            ],
            'invalid string' => [
                'cmchbtghtujvblturncgdcfdcjcigrvfhthknicibffh',
                '',
            ],
            'valid string' => [
                'ccchbtghtujvblturncgdcfdcjcigrvfhthknicibffh',
                'ccchbtghtujv',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getYubikeyIdFromOtpDataProvider
     */
    public function getYubikeyIdFromOtpReturnsExpectedResults(string $otp, string $expected): void
    {
        $result = $this->subject->getIdFromOtp($otp);
        self::assertSame($expected, $result);
    }

    public static function isYubikeyOtpDataProvider(): array
    {
        return [
            'empty string' => [
                '',
                false,
            ],
            'invalid string' => [
                'cmchbtghtujvblturncgdcfdcjcigrvfhthknicibffh',
                false,
            ],
            'string is too short' => [
                'ccchbtghtujvblturncgdcfdcjcigrvfhthknicibff',
                false,
            ],
            'string is too long' => [
                'ccchbtghtujvblturncgdcfdcjcigrvfhthknicibffff',
                false,
            ],
            'valid string' => [
                'ccchbtghtujvblturncgdcfdcjcigrvfhthknicibffh',
                true,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider isYubikeyOtpDataProvider
     */
    public function isYubikeyOtpReturnsExpectedResults(string $otp, bool $expected): void
    {
        $result = $this->subject->isOtp($otp);
        self::assertSame($expected, $result);
    }

    public static function isInYubikeysDataProvider(): array
    {
        return [
            'no yubikeys' => [
                [],
                'ccchbtghtujvblturncgdcfdcjcigrvfhthknicibffh',
                false,
            ],
            'no otp' => [
                [
                    [
                        'yubikeyId' => 'ccchbtghtujv',
                    ],
                ],
                '',
                false,
            ],
            'not in yubikeys' => [
                [
                    [
                        'yubikeyId' => 'ccchbtghtujv',
                    ],
                    [
                        'yubikeyId' => 'cbchbtghtujv',
                    ],
                ],
                'chchbtghtujvblturncgdcfdcjcigrvfhthknicibffh',
                false,
            ],
            'in yubikeys' => [
                [
                    [
                        'yubikeyId' => 'ccchbtghtujv',
                    ],
                    [
                        'yubikeyId' => 'cbchbtghtujv',
                    ],
                ],
                'cbchbtghtujvblturncgdcfdcjcigrvfhthknicibffh',
                true,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider isInYubikeysDataProvider
     */
    public function isInYubikeysReturnsExpectedResult(array $yubikeys, string $otp, bool $expected): void
    {
        $result = $this->subject->isInYubikeys($yubikeys, $otp);
        self::assertSame($expected, $result);
    }

    /**
     * @test
     */
    public function updateYubikeyUsageUpdatesLastUsedValue(): void
    {
        $timestamp = 1613302334845;
        $yubikeys = [
            [
                'yubikeyId' => 'cbchbtghtujv',
                'lastUsed' => '',
            ],
            [
                'yubikeyId' => 'ccchbtghtujv',
                'lastUsed' => '',
            ],
        ];

        $expected = $yubikeys;
        $expected[1]['lastUsed'] = $timestamp;

        $otp = 'ccchbtghtujvblturncgdcfdcjcigrvfhthknicibffh';

        $result = $this->subject->updateYubikeyUsage($yubikeys, $otp, $timestamp);
        self::assertSame($result, $expected);
    }

    /**
     * @test
     */
    public function deleteFromYubikeysDeletesYubikey(): void
    {
        $yubikeys = [
            [
                'yubikeyId' => 'cbchbtghtujv',
            ],
            [
                'yubikeyId' => 'ccchbtghtujv',
            ],
        ];

        $expected = $yubikeys;
        unset($expected[1]);

        $result = $this->subject->deleteFromYubikeys($yubikeys, 'ccchbtghtujv');
        self::assertSame($result, $expected);
    }
}
