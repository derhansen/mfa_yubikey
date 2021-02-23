<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'YubiKey OTP MFA provider',
    'description' => 'YubiKey OTP MFA provider for the TYPO3 backend login.',
    'category' => 'services',
    'author' => 'Torben Hansen',
    'author_email' => 'derhansen@gmail.com',
    'state' => 'beta',
    'uploadfolder' => 0,
    'clearCacheOnLoad' => 1,
    'version' => '0.1.1',
    'constraints' => [
        'depends' => [
            'typo3' => '11.1.0-11.1.99',
            'php' => '7.4.0-7.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
