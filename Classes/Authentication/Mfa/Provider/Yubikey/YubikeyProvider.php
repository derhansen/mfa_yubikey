<?php

declare(strict_types=1);

/*
 * This file is part of the Extension "mfa_yubikey" for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Derhansen\MfaYubikey\Authentication\Mfa\Provider\Yubikey;

use Derhansen\MfaYubikey\Service\YubikeyAuthService;
use Derhansen\MfaYubikey\Service\YubikeyService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderInterface;
use TYPO3\CMS\Core\Authentication\Mfa\MfaProviderPropertyManager;
use TYPO3\CMS\Core\Authentication\Mfa\MfaViewType;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;

class YubikeyProvider implements MfaProviderInterface
{
    private const LLL = 'LLL:EXT:mfa_yubikey/Resources/Private/Language/locallang.xlf:';
    private const MAX_ATTEMPTS = 3;

    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly Context $context,
        private readonly YubikeyAuthService $yubikeyAuthService,
        private readonly YubikeyService $yubikeyService,
        private readonly ViewFactoryInterface $viewFactory
    ) {}

    /**
     * Checks if a YubiKey OTP is in the current request
     */
    public function canProcess(ServerRequestInterface $request): bool
    {
        return $this->getYubikeyOtp($request) !== '';
    }

    /**
     * Evaluate if the provider is activated by checking the active state from the provider properties.
     */
    public function isActive(MfaProviderPropertyManager $propertyManager): bool
    {
        return (bool)$propertyManager->getProperty('active');
    }

    /**
     * Evaluate if the provider is temporarily locked by checking the current attempts state
     * from the provider properties.
     */
    public function isLocked(MfaProviderPropertyManager $propertyManager): bool
    {
        $attempts = (int)$propertyManager->getProperty('attempts', 0);
        return $attempts >= self::MAX_ATTEMPTS;
    }

    /**
     * Checks if the given OTP is a configured YubiKey for the current user and if so, verifies the OTP
     * against the configured authentication servers
     */
    public function verify(ServerRequestInterface $request, MfaProviderPropertyManager $propertyManager): bool
    {
        $otp = $this->getYubikeyOtp($request);
        $yubikeys = $propertyManager->getProperty('yubikeys');
        if (!$this->yubikeyService->isInYubikeys($yubikeys, $otp)) {
            // YubiKey not configured for user
            $attempts = $propertyManager->getProperty('attempts', 0);
            $propertyManager->updateProperties(['attempts' => ++$attempts]);
            return false;
        }

        $verified = $this->yubikeyAuthService->verifyOtp($otp);
        if (!$verified) {
            $attempts = $propertyManager->getProperty('attempts', 0);
            $propertyManager->updateProperties(['attempts' => ++$attempts]);
            return false;
        }

        $yubikeys = $this->yubikeyService->updateYubikeyUsage(
            $yubikeys,
            $otp,
            $this->context->getPropertyFromAspect('date', 'timestamp')
        );
        $propertyManager->updateProperties(['yubikeys' => $yubikeys]);

        return true;
    }

    /**
     * Initialize view and forward to the appropriate implementation
     * based on the view type to be returned.
     */
    public function handleRequest(
        ServerRequestInterface $request,
        MfaProviderPropertyManager $propertyManager,
        MfaViewType $type
    ): ResponseInterface {
        $viewFactoryData = new ViewFactoryData(
            templateRootPaths: ['EXT:mfa_yubikey/Resources/Private/Templates/'],
            partialRootPaths: ['EXT:mfa_yubikey/Resources/Private/Partials/'],
            request: $request,
        );
        $view = $this->viewFactory->create($viewFactoryData);
        $template = 'Auth';
        $variables = [];
        switch ($type) {
            case MfaViewType::SETUP:
                $variables = [
                    'provider' => $this,
                    'initialized' => $this->isAuthServiceInitialized(),
                ];
                $template = 'Setup';
                break;
            case MfaViewType::EDIT:
                $variables = [
                    'provider' => $this,
                    'yubikeys' => $propertyManager->getProperty('yubikeys'),
                    'initialized' => $this->isAuthServiceInitialized(),
                ];
                $template = 'Edit';
                break;
            case MfaViewType::AUTH:
                $variables = [
                    'provider' => $this,
                    'isLocked' => $this->isLocked($propertyManager),
                ];
                break;
        }
        $response = $this->responseFactory->createResponse();
        $response->getBody()->write($view->assignMultiple($variables)->render($template));
        return $response;
    }

    /**
     * Activate the provider by checking the necessary parameters
     */
    public function activate(ServerRequestInterface $request, MfaProviderPropertyManager $propertyManager): bool
    {
        if ($this->isActive($propertyManager)) {
            // Return since the user already activated this provider
            return true;
        }

        if (!$this->canProcess($request)) {
            // Return since the request can not be processed by this provider
            return false;
        }

        $newYubikey = $this->getNewYubikey($request);
        if (empty($newYubikey) && $this->isNewYubikeyRequest($request)) {
            // Either not YubiKey OTP given or OTP is wrong
            return false;
        }

        $yubikeys = [];
        $yubikeys[] = $newYubikey;

        $properties = [
            'yubikeys' => $yubikeys,
            'active' => true,
        ];

        // Usually there should be no entry if the provider is not activated, but to prevent the
        // provider from being unable to activate again, we update the existing entry in such case.
        return $propertyManager->hasProviderEntry()
            ? $propertyManager->updateProperties($properties)
            : $propertyManager->createProviderEntry($properties);
    }

    /**
     * Deactivates the provider for the current user
     */
    public function deactivate(ServerRequestInterface $request, MfaProviderPropertyManager $propertyManager): bool
    {
        if (!$this->isActive($propertyManager)) {
            // Return since this provider is not activated
            return false;
        }

        // Delete the provider entry
        return $propertyManager->deleteProviderEntry();
    }

    /**
     * Handle the unlock action by resetting the attempts provider property
     */
    public function unlock(ServerRequestInterface $request, MfaProviderPropertyManager $propertyManager): bool
    {
        if (!$this->isLocked($propertyManager)) {
            // Return since this provider is not locked
            return false;
        }

        // Reset the attempts
        return $propertyManager->updateProperties(['attempts' => 0]);
    }

    /**
     * Handle the save action for the provider. Takes care of adding/removing YubiKeys and updating
     * provider properties
     */
    public function update(ServerRequestInterface $request, MfaProviderPropertyManager $propertyManager): bool
    {
        $yubikeys = $propertyManager->getProperty('yubikeys');
        $otp = $this->getYubikeyOtp($request);
        $isNewYubikeyRequest = $this->isNewYubikeyRequest($request);
        $existingYubikey = $this->yubikeyService->isInYubikeys($yubikeys, $otp);

        if ($this->isDeleteYubikeyRequest($request)) {
            // Handle delete request
            $yubikeyToDelete = $request->getParsedBody()['delete'];
            $yubikeys = $this->yubikeyService->deleteFromYubikeys($yubikeys, $yubikeyToDelete);
            return $propertyManager->updateProperties(['yubikeys' => $yubikeys]);
        }

        if ($isNewYubikeyRequest && !$existingYubikey) {
            // Add new YubiKey
            $newYubiKey = $this->getNewYubikey($request);
            if (!empty($newYubiKey)) {
                $yubikeys[] = $newYubiKey;
                return $propertyManager->updateProperties(['yubikeys' => $yubikeys]);
            }
            $this->addFlashMessage(
                $this->getLanguageService()->sL(self::LLL . 'newYubikeyFailed.message'),
                $this->getLanguageService()->sL(self::LLL . 'newYubikeyFailed.title'),
                ContextualFeedbackSeverity::ERROR
            );
        }

        if ($isNewYubikeyRequest && $existingYubikey) {
            $this->addFlashMessage(
                $this->getLanguageService()->sL(self::LLL . 'yubikeyAlreadyConfigured.message'),
                $this->getLanguageService()->sL(self::LLL . 'yubikeyAlreadyConfigured.title'),
                ContextualFeedbackSeverity::WARNING
            );
        }

        // Provider properties successfully updated
        return true;
    }

    /**
     * Internal helper method for fetching the YubiKey OTP from the request for authentication
     */
    protected function getYubikeyOtp(ServerRequestInterface $request): string
    {
        return (string)($request->getQueryParams()['yubikey-otp'] ?? $request->getParsedBody()['yubikey-otp'] ?? '');
    }

    /**
     * Internal helper method for fetching a new YubiKey from the request. Also checks if the provided YubiKey OTP
     * id valid and extracts the YubiKey ID from the OTP.
     */
    protected function getNewYubikey(ServerRequestInterface $request): array
    {
        $yubikeyData = [];
        $name = (string)($request->getParsedBody()['yubikey-name'] ?? '');
        $yubikey = $this->getYubikeyOtp($request);

        $yubikeyId = $this->yubikeyService->getIdFromOtp($yubikey);

        if ($yubikeyId !== '') {
            $yubikeyData = [
                'name' => $name,
                'yubikeyId' => $yubikeyId,
                'dateAdded' => $this->context->getPropertyFromAspect('date', 'timestamp'),
                'lastUsed' => '',
            ];
        }

        return $yubikeyData;
    }

    protected function isNewYubikeyRequest(ServerRequestInterface $request): bool
    {
        return $this->getYubikeyOtp($request) !== '';
    }

    protected function isDeleteYubikeyRequest(ServerRequestInterface $request): bool
    {
        $delete = $request->getParsedBody()['delete'] ?? '';
        return $delete !== '';
    }

    protected function isAuthServiceInitialized(): bool
    {
        $initialized = $this->yubikeyAuthService->isInitialized();
        if (!$initialized) {
            $this->addFlashMessage(
                $this->getLanguageService()->sL(self::LLL . 'incompleteConfiguration.message'),
                $this->getLanguageService()->sL(self::LLL . 'incompleteConfiguration.title'),
                ContextualFeedbackSeverity::ERROR
            );
        }

        return $initialized;
    }

    protected function addFlashMessage(
        string $message,
        string $title = '',
        ContextualFeedbackSeverity $severity = ContextualFeedbackSeverity::INFO
    ): void {
        $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $message, $title, $severity, true);
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
