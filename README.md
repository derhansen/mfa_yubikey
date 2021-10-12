YubiKey two-factor MFA authentication for TYPO3
===============================================

[![Build Status](https://github.com/derhansen/mfa_yubikey/workflows/CI/badge.svg?branch=master)](https://github.com/derhansen/mfa_yubikey/actions)
[![Project Status: Active – The project has reached a stable, usable state and is being actively developed.](https://www.repostatus.org/badges/latest/active.svg)](https://www.repostatus.org/#active)

## What is it?

A MFA provider for TYPO3 11.5 which implements YubiKey OTP authentication

## Screenshot

![Edit YubiKey setup](/Documentation/Images/mfa_yubikey_edit.png)

## Documentation

Configuration steps

1. Enter you Yubico Client ID and Yubico Client Key in the extension settings
2. Switch to backend user settings and choose "Manage multi-factor authentication" in "Account security" tab
3. Setup the "YubiKey OTP MFA authentication" MFA provider by adding at lease one YubiKey
4. Ensure to set the "YubiKey OTP MFA authentication" as default MFA provider

## Support and updates

The extension is hosted on GitHub. Please report feedback, bugs and changerequest directly at https://github.com/derhansen/mfa_yubikey
