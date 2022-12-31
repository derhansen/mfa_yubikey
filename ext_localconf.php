<?php

defined('TYPO3') or die();

call_user_func(static function () {
    // Make YubiKey the recommended provider
    $GLOBALS['TYPO3_CONF_VARS']['BE']['recommendedMfaProvider'] = 'yubikey';
});
