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
use Griiv\EmailResend\Service\PendingEmailDataService;
use Griiv\Prestashop\Module\Contracts\Hook\Contracts\ActionHookInterface;
use Griiv\Prestashop\Module\Contracts\Hook\Hook;

class ActionEmailSendBefore extends Hook implements ActionHookInterface
{
    private PendingEmailDataService $pendingDataService;

    public function __construct(Context $context, PendingEmailDataService $pendingDataService)
    {
        parent::__construct($context);
        $this->pendingDataService = $pendingDataService;
    }

    /**
     * Execute hook - capture email data before sending
     */
    public function action($params): bool
    {
        // Generate unique key for this email
        $key = md5(serialize($params['to'] ?? '') . microtime(true) . mt_rand());

        // Store pending data via service
        $this->pendingDataService->store($key, [
            'templateVars' => $params['templateVars'] ?? [],
            'template' => $params['template'] ?? '',
            'templatePath' => $params['templatePath'] ?? '',
            'idLang' => (int) ($params['idLang'] ?? 0),
            'subject' => $params['subject'] ?? '',
            'to' => $params['to'] ?? '',
            'fileAttachment' => $params['fileAttachment'] ?? null,
            'timestamp' => microtime(true),
        ]);

        return true;
    }
}
