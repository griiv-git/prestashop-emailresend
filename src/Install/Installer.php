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

namespace Griiv\EmailResend\Install;

use Griiv\Prestashop\Module\Installer\GriivInstaller;

class Installer extends GriivInstaller
{
    public function install(): bool
    {
        return $this->installDatabase()
            && $this->registerHooks($this->module->getHooks())
            && $this->installTabs($this->module->getTabs());
    }

    public function uninstall(): bool
    {
        return $this->uninstallDatabase()
            && $this->unregisterHooks($this->module->getHooks())
            && $this->uninstallTabs($this->module->getTabs());
    }

    protected function installDatabase(): bool
    {
        $sqlFile = sprintf('%s/%s/sql/install.sql', _PS_MODULE_DIR_, $this->module->name);

        if (!file_exists($sqlFile) || !is_readable($sqlFile)) {
            return false;
        }

        $content = file_get_contents($sqlFile);
        $content = str_replace(['DB_PREFIX', 'MYSQL_ENGINE'], [_DB_PREFIX_, _MYSQL_ENGINE_], $content);

        $statements = array_filter(
            array_map('trim', explode(';', $content)),
            function ($s) { return !empty($s); }
        );

        return $this->executeQueries($statements);
    }

    protected function uninstallDatabase(): bool
    {
        $sqlFile = sprintf('%s/%s/sql/uninstall.sql', _PS_MODULE_DIR_, $this->module->name);

        if (!file_exists($sqlFile) || !is_readable($sqlFile)) {
            return false;
        }

        $content = file_get_contents($sqlFile);
        $content = str_replace(['DB_PREFIX', 'MYSQL_ENGINE'], [_DB_PREFIX_, _MYSQL_ENGINE_], $content);

        $statements = array_filter(
            array_map('trim', explode(';', $content)),
            function ($s) { return !empty($s); }
        );

        return $this->executeQueries($statements);
    }
}
