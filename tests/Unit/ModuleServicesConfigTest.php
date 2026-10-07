<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace PrestaShop\Module\BlockReassurance\Tests\Unit;

use PrestaShop\Module\BlockReassurance\Tests\ModuleTestCase;

class ModuleServicesConfigTest extends ModuleTestCase
{
    public function testRepositoryServiceIsPublicAndReceivesTheDatabasePrefix()
    {
        $common = file_get_contents(dirname(__DIR__, 2) . '/config/common.yml');

        $this->assertStringContainsString('block_reassurance_repository:', $common);
        $this->assertStringContainsString('PrestaShop\Module\BlockReassurance\Repository\PsreassuranceRepository', $common);
        $this->assertStringContainsString('public: true', $common);
        $this->assertStringContainsString("'@doctrine'", $common);
        $this->assertStringContainsString("'@doctrine.dbal.default_connection'", $common);
        $this->assertStringContainsString("'%database_prefix%'", $common);
    }

    public function testFormHandlerServiceIsPublicOnTheAdminContainer()
    {
        $admin = file_get_contents(dirname(__DIR__, 2) . '/config/admin/services.yml');

        $this->assertStringContainsString('block_reassurance_form_data_handler:', $admin);
        $this->assertStringContainsString('PrestaShop\Module\BlockReassurance\Form\PsreassuranceFormDataHandler', $admin);
        $this->assertStringContainsString('public: true', $admin);
        $this->assertStringContainsString("'@block_reassurance_repository'", $admin);
        $this->assertStringContainsString("'@prestashop.core.admin.lang.repository'", $admin);
        $this->assertStringContainsString("'@doctrine.orm.default_entity_manager'", $admin);
        $this->assertStringContainsString('../common.yml', $admin);
    }

    public function testFrontOfficeReusesTheCommonServiceFile()
    {
        $front = file_get_contents(dirname(__DIR__, 2) . '/config/front/services.yml');

        $this->assertStringContainsString('../common.yml', $front);
    }
}
