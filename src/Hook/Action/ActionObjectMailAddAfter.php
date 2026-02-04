<?php
/**
 * Copyright since 2024 Griiv
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 *
 * @author    Griiv
 * @copyright Since 2024 Griiv
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License ("AFL") v. 3.0
 */

declare(strict_types=1);

namespace Griiv\EmailResend\Hook\Action;

use Context;
use Griiv\EmailResend\Service\EmailCaptureService;
use Griiv\EmailResend\Service\PendingEmailDataService;
use Griiv\Prestashop\Module\Contracts\Hook\Contracts\ActionHookInterface;
use Griiv\Prestashop\Module\Contracts\Hook\Hook;

class ActionObjectMailAddAfter extends Hook implements ActionHookInterface
{
    private EmailCaptureService $captureService;
    private PendingEmailDataService $pendingDataService;

    public function __construct(
        Context $context,
        EmailCaptureService $captureService,
        PendingEmailDataService $pendingDataService
    ) {
        parent::__construct($context);
        $this->captureService = $captureService;
        $this->pendingDataService = $pendingDataService;
    }

    /**
     * Execute hook - store email content after Mail object is created
     */
    public function action($params): bool
    {
        if (!isset($params['object']) || !$params['object']->id) {
            return false;
        }

        $idMail = (int) $params['object']->id;
        $recipient = $params['object']->recipient;

        // Find matching pending data via service
        $matchingData = $this->pendingDataService->getByRecipient($recipient);

        if (!$matchingData) {
            // No pending data found, possibly email sent by other means
            return false;
        }

        // Generate final HTML content
        $htmlContent = $this->captureService->renderEmailHtml(
            $matchingData['templatePath'],
            $matchingData['template'],
            $matchingData['idLang'],
            $matchingData['templateVars']
        );

        if ($htmlContent) {
            try {
                // Store content
                $this->captureService->storeEmailContent($idMail, $htmlContent);

                // Store attachments if enabled and present
                if (!empty($matchingData['fileAttachment'])) {
                    $attachments = is_array($matchingData['fileAttachment'])
                        ? $matchingData['fileAttachment']
                        : [$matchingData['fileAttachment']];
                    $this->captureService->storeAttachments($idMail, $attachments);
                }
            } catch (\Exception $e) {
                \PrestaShopLogger::addLog(
                    'GriivEmailResend: Failed to store email content for id_mail=' . $idMail . ': ' . $e->getMessage(),
                    3,
                    null,
                    'Mail',
                    $idMail
                );
                return false;
            }
        }

        // Clean old pending data
        $this->pendingDataService->cleanOld();

        return true;
    }
}
