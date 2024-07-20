<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'YubiKey OTP MFA provider',
    'description' => 'YubiKey OTP MFA provider for the TYPO3 backend login.',
    'category' => 'services',
    'author' => 'Torben Hansen',
    'author_email' => 'derhansen@gmail.com',
    'state' => 'stable',
    'uploadfolder' => 0,
    'clearCacheOnLoad' => 1,
    'version' => '3.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.2.0-13.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
