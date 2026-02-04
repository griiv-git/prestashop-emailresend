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
}
