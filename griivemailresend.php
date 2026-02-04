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

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

use Griiv\EmailResend\Install\Installer;
use Griiv\Prestashop\Module\Contracts\Module\ModuleAbstract;
use PrestaShop\PrestaShop\Adapter\SymfonyContainer;

class GriivEmailResend extends ModuleAbstract
{
    /**
     * Namespace for hook classes
     */
    protected $nameSpace = 'Griiv\\EmailResend\\Hook\\';

    public function __construct()
    {
        $this->name = 'griivemailresend';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Griiv';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7.6.0', 'max' => _PS_VERSION_];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Email Resend', [], self::getTranslationDomain());
        $this->description = $this->trans('Allows to preview and resend emails from the email logs.', [], self::getTranslationDomain());
        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall this module?', [], self::getTranslationDomain());
    }

    /**
     * Get hooks to register
     */
    public function getHooks(): array
    {
        return [
            'actionEmailSendBefore',
            'actionObjectMailAddAfter',
            'displayBackOfficeHeader',
        ];
    }

    /**
     * Get tabs to register
     */
    public function getTabs(): array
    {
        return [
            [
                'name' => $this->trans('Email Resend', [], self::getTranslationDomain()),
                'class_name' => 'AdminGriivEmailResend',
                'route_name' => 'admin_griiv_email_resend_index',
                'visible' => false,
                'parent_class_name' => 'AdminAdvancedParameters',
                'wording' => 'Email Resend',
                'wording_domain' => self::getTranslationDomain(),
            ],
        ];
    }

    /**
     * Get translation domain
     */
    public static function getTranslationDomain(): string
    {
        return 'Modules.Griivemailresend.Admin';
    }

    public function install(): bool
    {
        $installer = new Installer($this);

        // Create uploads directory
        $uploadDir = _PS_MODULE_DIR_ . $this->name . '/uploads/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        // Create .htaccess in uploads
        $htaccess = $uploadDir . '.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Order deny,allow\nDeny from all");
        }

        return parent::install()
            && $installer->install()
            && Configuration::updateValue('GRIIV_EMAILRESEND_STORE_ATTACHMENTS', 0)
            && Configuration::updateValue('GRIIV_EMAILRESEND_STORAGE_MODE', 'database')
            && Configuration::updateValue('GRIIV_EMAILRESEND_MAX_SIZE', 10);
    }

    public function uninstall(): bool
    {
        $uninstaller = new Installer($this);

        // Remove uploads directory
        $uploadDir = _PS_MODULE_DIR_ . $this->name . '/uploads/';
        if (is_dir($uploadDir)) {
            Tools::deleteDirectory($uploadDir);
        }

        return $uninstaller->uninstall()
            && Configuration::deleteByName('GRIIV_EMAILRESEND_STORE_ATTACHMENTS')
            && Configuration::deleteByName('GRIIV_EMAILRESEND_STORAGE_MODE')
            && Configuration::deleteByName('GRIIV_EMAILRESEND_MAX_SIZE')
            && parent::uninstall();
    }

    /**
     * Module configuration page - redirect to Symfony controller
     */
    public function getContent(): string
    {
        Tools::redirectAdmin(
            SymfonyContainer::getInstance()->get('router')->generate('admin_griiv_email_resend_configuration')
        );
        return '';
    }
}
