YubiKey two-factor MFA authentication for TYPO3
===============================================

[![CI](https://github.com/derhansen/mfa_yubikey/actions/workflows/ci.yml/badge.svg)](https://github.com/derhansen/mfa_yubikey/actions/workflows/ci.yml)
[![Project Status: Active – The project has reached a stable, usable state and is being actively developed.](https://www.repostatus.org/badges/latest/active.svg)](https://www.repostatus.org/#active)

## What is it?

A MFA provider for TYPO3 CMS which implements YubiKey OTP authentication

## Screenshot

![Edit YubiKey setup](/Documentation/Images/mfa_yubikey_edit.png)

## Documentation

Configuration steps:

1. Obtain Yubico Client ID and Secret Key at https://upgrade.yubico.com/getapikey/
2. Enter you Yubico Client ID and Yubico Client Key in the extension settings
3. Switch to backend user settings and choose "Manage multi-factor authentication" in "Account security" tab
4. Setup the "YubiKey OTP MFA authentication" MFA provider by adding at least one YubiKey
5. (Optional) Ensure to set the "YubiKey OTP MFA authentication" as default MFA provider

### Using a self-hosted YubiCloud instance

Instead of using the official Yubico YubiCloud validation service, you can run
your own OTP validation server. This is useful for organizations that require
full control over their infrastructure or cannot use external services.

To use a self-hosted validation server:

1. Set up a self-hosted OTP validation server (see [Yubico's self-hosted OTP validation guide](https://developers.yubico.com/OTP/Guides/Self-hosted_OTP_validation.html))
2. In the extension settings, set the "YubiCloud API URL" to the URL of your own validation server
3. Enter the Client ID and Secret Key configured on your self-hosted instance

## Versions

| Version | TYPO3 | PHP       | Support/Development                  |
|---------|-------|-----------|--------------------------------------|
| 4.x     | 14.x  | 8.2 - 8.5 | Features, Bugfixes, Security Updates |
| 3.x     | 13.x  | 8.2 - 8.5 | Features, Bugfixes, Security Updates |
| 2.x     | 12.x  | 8.1 - 8.4 | Security Updates                     |
| 1.x     | 11.5  | 7.4 - 8.4 | Support dropped                      |

## Reporting a Vulnerability

Please report vulnerabilities to [security@typo3.org](mailto:security@typo3.org).

## Support and updates

The extension is hosted on GitHub. Please report feedback, bugs and change requests directly at https://github.com/derhansen/mfa_yubikey
