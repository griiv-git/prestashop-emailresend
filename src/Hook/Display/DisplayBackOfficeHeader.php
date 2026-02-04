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

namespace Griiv\EmailResend\Hook\Display;

use Context;
use Griiv\Prestashop\Module\Contracts\Hook\Contracts\DisplayHookInterface;
use Griiv\Prestashop\Module\Contracts\Hook\Hook;
use Media;
use Module;
use Symfony\Component\Routing\RouterInterface;

/**
 * Hook for displayBackOfficeHeader - loads JS/CSS on AdminEmails page
 */
class DisplayBackOfficeHeader extends Hook implements DisplayHookInterface
{
    private const TRANSLATION_DOMAIN = 'Modules.Griivemailresend.Admin';

    private RouterInterface $router;

    public function __construct(Context $context, RouterInterface $router)
    {
        parent::__construct($context);
        $this->router = $router;
    }

    /**
     * Execute hook - Load JS/CSS on AdminEmails page
     */
    public function display($params): string
    {
        // Only load on AdminEmails page
        if (!$this->isAdminEmailsPage()) {
            return '';
        }

        // Get module instance
        $module = Module::getInstanceByName('griivemailresend');
        if (!$module) {
            return '';
        }

        // Add CSS and JS
        $this->context->controller->addCSS($module->getPathUri() . 'views/css/resend.css');
        $this->context->controller->addJS($module->getPathUri() . 'views/js/resend.js');

        // Add JS definitions for AJAX URLs and translations
        Media::addJsDef([
            'griivResendUrls' => [
                'getContent' => $this->router->generate('admin_griiv_email_resend_get_content'),
                'getEmployees' => $this->router->generate('admin_griiv_email_resend_get_employees'),
                'resend' => $this->router->generate('admin_griiv_email_resend_send'),
            ],
            'griivResendTranslations' => [
                'loading' => $this->module->trans('Loading...', [], self::TRANSLATION_DOMAIN),
                'error' => $this->module->trans('An error occurred', [], self::TRANSLATION_DOMAIN),
                'noRecipient' => $this->module->trans('Please select at least one recipient', [], self::TRANSLATION_DOMAIN),
                'maxRecipients' => $this->module->trans('Maximum 10 recipients allowed', [], self::TRANSLATION_DOMAIN),
                'invalidEmail' => $this->module->trans('Invalid email address', [], self::TRANSLATION_DOMAIN),
                'sending' => $this->module->trans('Sending...', [], self::TRANSLATION_DOMAIN),
                'sent' => $this->module->trans('Email sent successfully', [], self::TRANSLATION_DOMAIN),
                'sendError' => $this->module->trans('Failed to send email', [], self::TRANSLATION_DOMAIN),
            ],
        ]);

        // Return modal HTML
        return $module->fetch('module:griivemailresend/views/templates/admin/modal_resend.tpl');
    }

    /**
     * Check if current page is AdminEmails
     */
    private function isAdminEmailsPage(): bool
    {
        $controllerName = $this->context->controller->controller_name ?? '';
        $phpSelf = $this->context->controller->php_self ?? '';

        return $controllerName === 'AdminEmails' || $phpSelf === 'AdminEmails';
    }
}
