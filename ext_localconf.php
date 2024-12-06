<?php

defined('TYPO3') or die();

// Make YubiKey the recommended provider
$GLOBALS['TYPO3_CONF_VARS']['BE']['recommendedMfaProvider'] = 'yubikey';
