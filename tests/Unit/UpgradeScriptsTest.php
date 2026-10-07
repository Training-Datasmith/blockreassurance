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

class UpgradeScriptsTest extends ModuleTestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__, 2) . '/upgrade/upgrade-4.0.0.php';
        require_once dirname(__DIR__, 2) . '/upgrade/upgrade-5.1.0.php';
        require_once dirname(__DIR__, 2) . '/upgrade/upgrade-6.0.0.php';
    }

    public function testUpgrade400MigratesLegacyRowsAndRegistersMissingHooks()
    {
        \Language::$languages = [
            ['id_lang' => 1, 'locale' => 'en-US'],
            ['id_lang' => 3, 'locale' => 'es-ES'],
        ];
        \Db::getInstance()->executeSQueue = [
            [
                ['id_reassurance' => 4, 'id_lang' => 3, 'text' => 'Hello'],
            ],
            [
                ['id_reassurance' => 4, 'id_shop' => 2, 'file_name' => 'secure.png'],
            ],
        ];
        $module = $this->createModule();
        $module->hooksAlreadyRegistered = ['displayReassurance'];

        $result = upgrade_module_4_0_0($module);

        $this->assertSame(1, $result);
        $this->assertCount(1, \Tab::$created);
        $tab = \Tab::$created[0];
        $this->assertSame('AdminBlockListing', $tab->class_name);
        $this->assertSame(-1, $tab->id_parent);
        $this->assertSame('blockreassurance', $tab->module);
        $this->assertTrue($tab->added);
        $this->assertTrue($tab->active);
        $this->assertSame([1 => 'blockreassurance', 3 => 'blockreassurance'], $tab->name);
        $this->assertContains(true, \Language::$calls);
        $sql = implode("\n", \Db::getInstance()->executed);
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS `ps_psreassurance`', $sql);
        $this->assertStringContainsString('`id_shop` int(10) unsigned NOT NULL', $sql);
        $this->assertStringContainsString('TRUNCATE TABLE `ps_psreassurance`', $sql);
        $this->assertStringContainsString('TRUNCATE TABLE `ps_psreassurance_lang`', $sql);
        $this->assertStringContainsString($module->old_path_img . 'secure.png', $sql);
        $this->assertStringContainsString('4, 3, 1, \'Hello\'', $sql);
        $this->assertStringContainsString(', 2, null, null, now())', $sql);
        $this->assertSame([
            'displayAfterBodyOpeningTag',
            'displayNavFullWidth',
            'displayFooterAfter',
            'displayFooterBefore',
            'actionFrontControllerSetMedia',
        ], $module->registeredHooks);
        $this->assertNotContains('displayReassurance', $module->registeredHooks);
        $this->assertSame('0', \Configuration::get('PSR_HOOK_HEADER'));
        $this->assertSame('0', \Configuration::get('PSR_HOOK_FOOTER'));
        $this->assertSame('1', \Configuration::get('PSR_HOOK_PRODUCT'));
        $this->assertSame('1', \Configuration::get('PSR_HOOK_CHECKOUT'));
        $this->assertSame('#F19D76', \Configuration::get('PSR_ICON_COLOR'));
        $this->assertSame('#000000', \Configuration::get('PSR_TEXT_COLOR'));
    }

    public function testUpgrade400ReturnsTrueWhenEveryHookIsAlreadyRegistered()
    {
        \Db::getInstance()->executeSQueue = [[], []];
        $module = $this->createModule();
        $module->hooksAlreadyRegistered = [
            'displayAfterBodyOpeningTag',
            'displayNavFullWidth',
            'displayFooterAfter',
            'displayFooterBefore',
            'displayReassurance',
            'actionFrontControllerSetMedia',
        ];

        $this->assertSame(true, upgrade_module_4_0_0($module));
        $this->assertSame([], $module->registeredHooks);
    }

    public function testUpgrade400ReturnsTheDatabaseErrorAndSkipsConfiguration()
    {
        \Db::getInstance()->executeSQueue = [[], []];
        \Db::getInstance()->executeResults = [false];
        \Db::getInstance()->msgError = 'duplicate table';
        $module = $this->createModule();

        $this->assertFalse(upgrade_module_4_0_0($module));
        $this->assertSame([], \Configuration::$updates);
        $this->assertSame([], $module->registeredHooks);
    }

    public function testUpgrade400CollapsesToZeroWhenHookRegistrationFails()
    {
        \Db::getInstance()->executeSQueue = [[], []];
        $module = $this->createModule();
        $module->registerHookResult = false;

        $this->assertSame(0, upgrade_module_4_0_0($module));
    }

    public function testUpgrade510RewritesShopColumnsForTheDefaultShop()
    {
        \Configuration::$values['PS_SHOP_DEFAULT'] = 3;

        $this->assertTrue(upgrade_module_5_1_0($this->createModule()));

        $sql = \Db::getInstance()->executed;
        $this->assertCount(4, $sql);
        $this->assertStringContainsString('DELETE FROM `ps_psreassurance_lang` WHERE `id_shop` != 3', $sql[0]);
        $this->assertStringContainsString('ADD PRIMARY KEY(`id_psreassurance`,`id_lang`)', $sql[1]);
        $this->assertStringContainsString('ALTER TABLE `ps_psreassurance` DROP `id_shop`', $sql[2]);
        $this->assertStringContainsString('ALTER TABLE `ps_psreassurance_lang` DROP `id_shop`', $sql[3]);
    }

    public function testUpgrade510ReturnsTheFailingStatementAndStops()
    {
        \Configuration::$values['PS_SHOP_DEFAULT'] = 1;
        \Db::getInstance()->executeResults = [true, false];
        \Db::getInstance()->msgError = 'cannot drop key';

        $this->assertFalse(upgrade_module_5_1_0($this->createModule()));
        $this->assertCount(2, \Db::getInstance()->executed);
    }

    public function testUpgrade600KeepsTheLastThreeIconSegmentsAndCustomBasenames()
    {
        \Db::getInstance()->rows = [
            [
                'id_psreassurance' => 1,
                'icon' => 'modules/blockreassurance/views/img/reassurance/pack2/security.svg',
                'custom_icon' => '/ignored/custom.svg',
            ],
            [
                'id_psreassurance' => 2,
                'icon' => '',
                'custom_icon' => '/modules/blockreassurance/views/img/img_perso/mine.svg',
            ],
            [
                'id_psreassurance' => 3,
                'icon' => 'security.svg',
                'custom_icon' => '',
            ],
            [
                'id_psreassurance' => 4,
                'icon' => '',
                'custom_icon' => '',
            ],
            [
                'id_psreassurance' => '8abc',
                'icon' => '0',
                'custom_icon' => 'folder/only.png',
            ],
        ];

        $this->assertTrue(upgrade_module_6_0_0($this->createModule()));

        $this->assertSame([
            [
                'table' => 'psreassurance',
                'data' => ['icon' => 'reassurance/pack2/security.svg'],
                'where' => '`id_psreassurance` = 1',
            ],
            [
                'table' => 'psreassurance',
                'data' => ['custom_icon' => 'mine.svg'],
                'where' => '`id_psreassurance` = 2',
            ],
            [
                'table' => 'psreassurance',
                'data' => ['icon' => 'security.svg'],
                'where' => '`id_psreassurance` = 3',
            ],
            [
                'table' => 'psreassurance',
                'data' => ['custom_icon' => 'only.png'],
                'where' => '`id_psreassurance` = 8',
            ],
        ], \Db::getInstance()->updates);
    }

    public function testUpgrade600ReturnsTrueWhenThereAreNoRows()
    {
        \Db::getInstance()->rows = [];

        $this->assertTrue(upgrade_module_6_0_0($this->createModule()));
        $this->assertSame([], \Db::getInstance()->updates);
    }

    public function testUpgrade600ReturnsTrueEvenWhenAnUpdateFails()
    {
        \Db::getInstance()->rows = [
            ['id_psreassurance' => 1, 'icon' => 'a/b/c.svg', 'custom_icon' => ''],
        ];
        \Db::getInstance()->updateResult = false;

        $this->assertTrue(upgrade_module_6_0_0($this->createModule()));
        $this->assertCount(1, \Db::getInstance()->updates);
    }
}
