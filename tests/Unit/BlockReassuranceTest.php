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

use PrestaShop\Module\BlockReassurance\Entity\Psreassurance;
use PrestaShop\Module\BlockReassurance\Tests\ModuleTestCase;

class BlockReassuranceTest extends ModuleTestCase
{
    public function testConstructorPublishesIdentityPathsAndCompliance()
    {
        $module = $this->createModule();

        $this->assertSame('blockreassurance', $module->name);
        $this->assertSame('6.0.0', $module->version);
        $this->assertSame('PrestaShop', $module->author);
        $this->assertFalse($module->need_instance);
        $this->assertTrue($module->bootstrap);
        $this->assertSame('Customer Reassurance', $module->displayName);
        $this->assertSame('Are you sure you want to uninstall this module?', $module->confirmUninstall);
        $this->assertSame(['min' => '1.7.8', 'max' => _PS_VERSION_], $module->ps_versions_compliancy);
        $this->assertSame('http://localhost/', $module->ps_url);
        $this->assertSame('/modules/blockreassurance/', $module->_path);
        $this->assertSame('/modules/blockreassurance/views/img/', $module->img_path);
        $this->assertSame('/modules/blockreassurance/views/img/img_perso', $module->img_path_perso);
        $this->assertSame(_PS_MODULE_DIR_ . 'blockreassurance/views/img/img_perso/', $module->folder_file_upload);
        $this->assertSame($module->img_path, \blockreassurance::$static_img_path);
        $this->assertSame($module->img_path_perso, \blockreassurance::$static_img_path_perso);
        $this->assertSame($module->folder_file_upload, \blockreassurance::$static_folder_file_upload);
    }

    public function testPositionHookAndControllerConstants()
    {
        $this->assertSame(0, \blockreassurance::POSITION_NONE);
        $this->assertSame(1, \blockreassurance::POSITION_BELOW_HEADER);
        $this->assertSame(2, \blockreassurance::POSITION_ABOVE_HEADER);
        $this->assertSame('PSR_HOOK_HEADER', \blockreassurance::PSR_HOOK_HEADER);
        $this->assertSame('PSR_HOOK_FOOTER', \blockreassurance::PSR_HOOK_FOOTER);
        $this->assertSame('PSR_HOOK_PRODUCT', \blockreassurance::PSR_HOOK_PRODUCT);
        $this->assertSame('PSR_HOOK_CHECKOUT', \blockreassurance::PSR_HOOK_CHECKOUT);
        $this->assertSame(['cart', 'order'], \blockreassurance::ALLOWED_CONTROLLERS_CHECKOUT);
        $this->assertSame(['product'], \blockreassurance::ALLOWED_CONTROLLERS_PRODUCT);
    }

    /**
     * @dataProvider blockProductDisplayCases
     */
    public function testBlockProductDisplayRules($enableCheckout, $enableProduct, $controller, $expected)
    {
        $module = $this->createModule();

        $this->assertSame(
            $expected,
            $this->callPrivate($module, 'shouldWeDisplayOnBlockProduct', [$enableCheckout, $enableProduct, $controller])
        );
    }

    public static function blockProductDisplayCases()
    {
        return [
            'cart with checkout enabled' => [1, 0, 'cart', true],
            'order with checkout enabled' => [1, 0, 'order', true],
            'cart with checkout disabled' => [0, 1, 'cart', false],
            'cart with above-header position' => [2, 1, 'cart', false],
            'product page enabled' => [0, 1, 'product', true],
            'product page disabled' => [1, 0, 'product', false],
            'product page above header' => [1, 2, 'product', false],
            'both enabled on cart' => [1, 1, 'cart', true],
            'both enabled on product' => [1, 1, 'product', true],
            'unknown controller' => [1, 1, 'index', false],
            'empty controller' => [1, 1, '', false],
            'controller match is case sensitive' => [1, 1, 'Cart', false],
            'padded controller does not match' => [1, 1, 'product ', false],
        ];
    }

    public function testInstallCreatesTablesSeedsBlocksAndRegistersHooks()
    {
        \Language::$languages = [
            ['id_lang' => 1, 'locale' => 'en-US'],
            ['id_lang' => 2, 'locale' => 'fr-FR'],
        ];
        $module = $this->createModule();

        $this->assertTrue($module->install());

        $sql = implode("\n", \Db::getInstance()->executed);
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS `ps_psreassurance`', $sql);
        $this->assertStringContainsString('CREATE TABLE IF NOT EXISTS `ps_psreassurance_lang`', $sql);
        $this->assertStringContainsString('ENGINE=InnoDB', $sql);
        $this->assertStringContainsString('reassurance/pack2/security.svg', $sql);
        $this->assertStringContainsString('reassurance/pack2/carrier.svg', $sql);
        $this->assertStringContainsString('reassurance/pack2/parcel.svg', $sql);
        $this->assertSame(2, substr_count($sql, 'Security policy'));
        $this->assertStringContainsString('(1, 1,', $sql);
        $this->assertStringContainsString('(1, 2,', $sql);
        $this->assertStringNotContainsString('id_shop', $sql);

        $this->assertSame(\blockreassurance::POSITION_NONE, \Configuration::get('PSR_HOOK_HEADER'));
        $this->assertSame(\blockreassurance::POSITION_NONE, \Configuration::get('PSR_HOOK_FOOTER'));
        $this->assertSame(\blockreassurance::POSITION_BELOW_HEADER, \Configuration::get('PSR_HOOK_PRODUCT'));
        $this->assertSame(\blockreassurance::POSITION_BELOW_HEADER, \Configuration::get('PSR_HOOK_CHECKOUT'));
        $this->assertSame('#F19D76', \Configuration::get('PSR_ICON_COLOR'));
        $this->assertSame('#000000', \Configuration::get('PSR_TEXT_COLOR'));
        $this->assertSame([
            'displayAfterBodyOpeningTag',
            'displayNavFullWidth',
            'displayFooterAfter',
            'displayFooterBefore',
            'displayReassurance',
            'actionFrontControllerSetMedia',
        ], $module->registeredHooks);
        $this->assertContains(false, \Language::$calls);
        $this->assertSame([], $module->_errors);
    }

    public function testInstallStopsBeforeConfigurationWhenAQueryFails()
    {
        \Db::getInstance()->executeResults = [true, false];
        $module = $this->createModule();

        $this->assertFalse($module->install());
        $this->assertSame([], \Configuration::$updates);
        $this->assertSame([], $module->registeredHooks);
        $this->assertSame([], $module->_errors);
    }

    public function testInstallSucceedsWithoutLanguages()
    {
        \Language::$languages = [];
        $module = $this->createModule();

        $this->assertTrue($module->install());
        $sql = implode("\n", \Db::getInstance()->executed);
        $this->assertStringNotContainsString('psreassurance_lang (id_psreassurance', $sql);
        $this->assertCount(6, $module->registeredHooks);
    }

    public function testInstallReportsAHookRegistrationFailure()
    {
        $module = $this->createModule();
        $module->registerHookResult = false;

        $this->assertFalse($module->install());
        $this->assertSame(['displayAfterBodyOpeningTag'], $module->registeredHooks);
        $this->assertNotEmpty($module->_errors);
        $this->assertStringContainsString('error during the installation', $module->_errors[0]);
        $this->assertSame(\blockreassurance::POSITION_NONE, \Configuration::get('PSR_HOOK_HEADER'));
    }

    public function testInstallReportsAParentInstallFailure()
    {
        $module = $this->createModule();
        $module->parentInstallResult = false;

        $this->assertFalse($module->install());
        $this->assertSame([], $module->registeredHooks);
        $this->assertNotEmpty($module->_errors);
    }

    public function testUninstallDropsTablesAndConfiguration()
    {
        $module = $this->createModule();

        $this->assertTrue($module->uninstall());

        $this->assertSame(
            ['DROP TABLE IF EXISTS `ps_psreassurance`, `ps_psreassurance_lang`'],
            \Db::getInstance()->executed
        );
        $this->assertSame([
            'PSR_HOOK_HEADER',
            'PSR_HOOK_FOOTER',
            'PSR_HOOK_PRODUCT',
            'PSR_HOOK_CHECKOUT',
            'PSR_ICON_COLOR',
            'PSR_TEXT_COLOR',
        ], \Configuration::$deleted);
        $this->assertSame([], $module->_errors);
    }

    public function testUninstallStopsWhenTheDropFails()
    {
        \Db::getInstance()->executeResults = [false];
        $module = $this->createModule();

        $this->assertFalse($module->uninstall());
        $this->assertSame([], \Configuration::$deleted);
        $this->assertSame([], $module->_errors);
    }

    public function testUninstallReportsAParentFailureAfterConfigurationIsRemoved()
    {
        $module = $this->createModule();
        $module->parentUninstallResult = false;

        $this->assertFalse($module->uninstall());
        $this->assertCount(6, \Configuration::$deleted);
        $this->assertStringContainsString('error during the uninstallation', $module->_errors[0]);
    }

    public function testHeaderAndFooterHooksRenderOnlyForTheConfiguredPosition()
    {
        $module = $this->moduleWithBlocks([
            ['icon' => 'icon.svg', 'custom_icon' => '', 'title' => 'T', 'description' => 'D', 'type_link' => 0, 'link' => ''],
        ]);
        \Configuration::$values = [
            'PSR_HOOK_HEADER' => \blockreassurance::POSITION_ABOVE_HEADER,
            'PSR_HOOK_FOOTER' => \blockreassurance::POSITION_BELOW_HEADER,
            'PSR_ICON_COLOR' => '#111111',
            'PSR_TEXT_COLOR' => '#222222',
        ];

        $this->assertSame('views/templates/hook/displayBlock.tpl', $module->hookdisplayAfterBodyOpeningTag([]));
        $this->assertSame('', $module->hookdisplayNavFullWidth([]));
        $this->assertSame('views/templates/hook/displayBlockWhite.tpl', $module->hookdisplayFooterAfter([]));
        $this->assertSame('', $module->hookdisplayFooterBefore([]));
        $this->assertSame('#111111', $module->context->smarty->assigned['iconColor']);
        $this->assertSame('#222222', $module->context->smarty->assigned['textColor']);
        $this->assertSame(Psreassurance::TYPE_LINK_NONE, $module->context->smarty->assigned['LINK_TYPE_NONE']);
        $this->assertSame(Psreassurance::TYPE_LINK_CMS_PAGE, $module->context->smarty->assigned['LINK_TYPE_CMS']);
        $this->assertSame(Psreassurance::TYPE_LINK_URL, $module->context->smarty->assigned['LINK_TYPE_URL']);
        $this->assertSame([1, 1], $module->services['block_reassurance_repository']->statusLanguageIds);
    }

    public function testBelowHeaderAndAboveFooterUseTheMatchingTemplates()
    {
        $module = $this->moduleWithBlocks([]);
        \Configuration::$values = [
            'PSR_HOOK_HEADER' => '1',
            'PSR_HOOK_FOOTER' => '2',
        ];

        $this->assertSame('', $module->hookdisplayAfterBodyOpeningTag([]));
        $this->assertSame('views/templates/hook/displayBlock.tpl', $module->hookdisplayNavFullWidth([]));
        $this->assertSame('', $module->hookdisplayFooterAfter([]));
        $this->assertSame('views/templates/hook/displayBlockWhite.tpl', $module->hookdisplayFooterBefore([]));
    }

    public function testDisabledPositionsRenderNothingAndDoNotLoadBlocks()
    {
        $module = $this->moduleWithBlocks([]);
        \Configuration::$values = [
            'PSR_HOOK_HEADER' => \blockreassurance::POSITION_NONE,
            'PSR_HOOK_FOOTER' => \blockreassurance::POSITION_NONE,
        ];

        $this->assertSame('', $module->hookdisplayAfterBodyOpeningTag([]));
        $this->assertSame('', $module->hookdisplayNavFullWidth([]));
        $this->assertSame('', $module->hookdisplayFooterAfter([]));
        $this->assertSame('', $module->hookdisplayFooterBefore([]));
        $this->assertSame([], $module->services['block_reassurance_repository']->statusLanguageIds);
    }

    public function testReassuranceHookRendersOnEnabledCheckoutAndProductControllersOnly()
    {
        $module = $this->moduleWithBlocks([]);
        \Configuration::$values = [
            'PSR_HOOK_CHECKOUT' => \blockreassurance::POSITION_BELOW_HEADER,
            'PSR_HOOK_PRODUCT' => \blockreassurance::POSITION_NONE,
        ];
        \Tools::$values['controller'] = 'order';

        $this->assertSame('views/templates/hook/displayBlockProduct.tpl', $module->hookdisplayReassurance([]));

        \Tools::$values['controller'] = 'product';
        $this->assertSame('', $module->hookdisplayReassurance([]));
        $this->assertSame([1], $module->services['block_reassurance_repository']->statusLanguageIds);
    }

    public function testFrontControllerMediaRegistersAssetsAndTheIconColor()
    {
        \Configuration::$values['PSR_ICON_COLOR'] = '#abcdef';
        $module = $this->createModule();

        $module->hookActionFrontControllerSetMedia();

        $this->assertSame([
            ['psr_icon_color' => '#abcdef'],
        ], \Media::$jsDefs);
        $this->assertSame(
            'modules/blockreassurance/views/dist/front.css',
            $module->context->controller->stylesheets['front-css']
        );
        $this->assertSame(
            'modules/blockreassurance/views/dist/front.js',
            $module->context->controller->javascripts['front-js']
        );
    }

    public function testWidgetVariablesPreferTheStockIconAndConcatenateText()
    {
        $module = $this->createModule();
        $repository = new \InMemoryBlockRepository();
        $repository->activeBlocks = [
            3 => [
                'icon' => 'reassurance/pack2/security.svg',
                'custom_icon' => 'mine.svg',
                'title' => 'Security',
                'description' => 'Encrypted',
                'type_link' => Psreassurance::TYPE_LINK_URL,
                'link' => 'https://example.test',
            ],
            4 => [
                'icon' => '',
                'custom_icon' => 'custom.svg',
                'title' => 'Delivery',
                'description' => '',
                'type_link' => Psreassurance::TYPE_LINK_NONE,
                'link' => '',
            ],
            5 => [
                'icon' => '0',
                'custom_icon' => '',
                'title' => 'Returns',
                'description' => '30 days',
                'type_link' => null,
                'link' => null,
            ],
        ];
        $module->services['block_reassurance_repository'] = $repository;
        $module->context->language->id = 6;

        $variables = $module->getWidgetVariables('displayReassurance', ['unused' => true]);

        $this->assertSame([6], $repository->statusLanguageIds);
        $this->assertSame(Psreassurance::TYPE_LINK_NONE, $variables['LINK_TYPE_NONE']);
        $this->assertSame([3, 4, 5], array_keys($variables['elements']));
        $this->assertSame('reassurance/pack2/security.svg', $variables['elements'][3]['image']);
        $this->assertSame('Security Encrypted', $variables['elements'][3]['text']);
        $this->assertSame('Security', $variables['elements'][3]['title']);
        $this->assertSame(Psreassurance::TYPE_LINK_URL, $variables['elements'][3]['type_link']);
        $this->assertSame('https://example.test', $variables['elements'][3]['link']);
        $this->assertSame('custom.svg', $variables['elements'][4]['image']);
        $this->assertSame('Delivery ', $variables['elements'][4]['text']);
        $this->assertSame('', $variables['elements'][5]['image']);
        $this->assertSame('Returns 30 days', $variables['elements'][5]['text']);
    }

    public function testWidgetVariablesOnAnEmptyCatalog()
    {
        $module = $this->moduleWithBlocks([]);

        $variables = $module->getWidgetVariables(null, []);

        $this->assertSame([], $variables['elements']);
        $this->assertSame(0, $variables['LINK_TYPE_NONE']);
    }

    public function testRenderWidgetSkipsTheLegacyDisplayFooterHook()
    {
        $module = $this->createModule();

        $this->assertSame('', $module->renderWidget('displayFooter', []));
        $this->assertSame([], $module->fetched);
    }

    public function testRenderWidgetUsesTheCacheWithoutRebuildingVariables()
    {
        $module = $this->createModule();
        $module->cached = true;
        $module->smarty->assigned['marker'] = 'keep';

        $rendered = $module->renderWidget('displayReassurance', []);

        $this->assertSame('fetched:module:blockreassurance/views/templates/hook/blockreassurance.tpl', $rendered);
        $this->assertSame([[
            'module:blockreassurance/views/templates/hook/blockreassurance.tpl',
            'cache-blockreassurance',
        ]], $module->fetched);
        $this->assertSame('keep', $module->smarty->assigned['marker']);
        $this->assertArrayNotHasKey('elements', $module->smarty->assigned);
    }

    public function testRenderWidgetAssignsFreshVariablesWhenTheCacheIsCold()
    {
        $module = $this->moduleWithBlocks([
            [
                'icon' => 'icon.svg',
                'custom_icon' => '',
                'title' => 'Title',
                'description' => 'Body',
                'type_link' => 0,
                'link' => '',
            ],
        ]);

        $module->renderWidget('displayNavFullWidth', []);

        $this->assertSame('icon.svg', $module->smarty->assigned['elements'][0]['image']);
        $this->assertSame('Title Body', $module->smarty->assigned['elements'][0]['text']);
    }

    public function testLoadAssetPublishesBackOfficeFilesAndJsDefinitions()
    {
        \Configuration::$values = [
            'PSR_ICON_COLOR' => '#123456',
            'PSR_TEXT_COLOR' => '#654321',
            'PS_LANG_DEFAULT' => '2',
        ];
        $module = $this->createModule();

        $module->loadAsset();

        $this->assertSame([
            ['/modules/blockreassurance/views/dist/back.css', 'all'],
        ], $module->context->controller->css);
        $this->assertSame(['/modules/blockreassurance/views/dist/back.js'], $module->context->controller->js);
        $definitions = \Media::$jsDefs[0];
        $this->assertSame('#123456', $definitions['psr_icon_color']);
        $this->assertSame('#654321', $definitions['psr_text_color']);
        $this->assertSame('AdminBlockListing', $definitions['psr_controller_block']);
        $this->assertStringContainsString('controller=AdminBlockListing', $definitions['psr_controller_block_url']);
        $this->assertSame(2, $definitions['psr_lang']);
        $this->assertSame('Block updated', $definitions['block_updated']);
        $this->assertSame('Position changed successfully!', $definitions['successPosition']);
        $this->assertArrayHasKey('min_field_error', $definitions);
        $this->assertArrayHasKey('txtConfirmRemoveBlock', $definitions);
    }

    public function testGetContentDefaultsToTheGlobalPageAndPrefixesIcons()
    {
        \CMS::$pages = [['id_cms' => 4, 'meta_title' => 'Shipping']];
        \Language::$languages = [
            ['id_lang' => 1, 'locale' => 'en-US'],
        ];
        $module = $this->createModule();
        $repository = new \InMemoryBlockRepository();
        $repository->allBlocks = [
            1 => ['icon' => 'reassurance/pack2/security.svg', 'custom_icon' => 'ignored.svg'],
            2 => ['icon' => '', 'custom_icon' => 'mine.svg'],
            3 => ['icon' => '', 'custom_icon' => ''],
        ];
        $module->services['block_reassurance_repository'] = $repository;
        $module->folder_file_upload = sys_get_temp_dir() . '/br-missing-content-' . uniqid('', true);
        \Configuration::$values = [
            'PSR_HOOK_HEADER' => '0',
            'PSR_HOOK_FOOTER' => '2',
            'PSR_HOOK_PRODUCT' => '1',
            'PSR_HOOK_CHECKOUT' => '1',
            'PSR_TEXT_COLOR' => '#000000',
            'PSR_ICON_COLOR' => '#F19D76',
        ];

        $rendered = $module->getContent();
        $assigned = $module->context->smarty->assigned;

        $this->assertSame('views/templates/admin/configure.tpl', $rendered);
        $this->assertSame('global', $assigned['currentPage']);
        $this->assertSame(0, $assigned['psr_hook_header']);
        $this->assertSame(2, $assigned['psr_hook_footer']);
        $this->assertSame('#F19D76', $assigned['psr_icon_color']);
        $this->assertSame('/modules/blockreassurance/views/img/reassurance/pack2/security.svg', $assigned['allblock'][1]['icon']);
        $this->assertSame('ignored.svg', $assigned['allblock'][1]['custom_icon']);
        $this->assertSame('/modules/blockreassurance/views/img/img_perso/mine.svg', $assigned['allblock'][2]['custom_icon']);
        $this->assertSame('', $assigned['allblock'][3]['icon']);
        $this->assertSame(\CMS::$pages, $assigned['allCms']);
        $this->assertSame(1, $assigned['defaultFormLanguage']);
        $this->assertStringContainsString('configure=blockreassurance', $assigned['moduleAdminLink']);
        $this->assertArrayHasKey('position', $assigned['fields_captions']);
        $this->assertFalse($assigned['folderIsWritable']);
    }

    public function testGetContentUsesTheRequestedPage()
    {
        $module = $this->moduleWithRepositoryBlocks([]);
        $module->folder_file_upload = sys_get_temp_dir() . '/br-missing-content-' . uniqid('', true);
        \Tools::$values['page'] = 'appearance';

        $module->getContent();

        $this->assertSame('appearance', $module->context->smarty->assigned['currentPage']);
    }

    public function testGetContentTreatsAZeroPageValueAsTheGlobalPage()
    {
        $module = $this->moduleWithRepositoryBlocks([]);
        \Tools::$values['page'] = '0';

        $module->getContent();

        $this->assertSame('global', $module->context->smarty->assigned['currentPage']);
    }

    public function testUploadDirectoryRightsRequireWriteAndExecuteOnUnix()
    {
        if (DIRECTORY_SEPARATOR !== '/' || (function_exists('posix_geteuid') && posix_geteuid() === 0)) {
            $this->markTestSkipped('Unix mode bits are only meaningful for a non-root user.');
        }

        $directory = sys_get_temp_dir() . '/br-upload-' . uniqid('', true);
        mkdir($directory, 0755);
        $module = $this->createModule();
        $module->folder_file_upload = $directory;

        try {
            chmod($directory, 0755);
            $this->assertTrue($this->callPrivate($module, 'folderUploadFilesHasGoodRights'));

            chmod($directory, 0644);
            $this->assertFalse($this->callPrivate($module, 'folderUploadFilesHasGoodRights'));

            chmod($directory, 0555);
            $this->assertFalse($this->callPrivate($module, 'folderUploadFilesHasGoodRights'));
        } finally {
            chmod($directory, 0755);
            rmdir($directory);
        }
    }

    public function testMissingUploadDirectoryIsRejected()
    {
        $module = $this->createModule();
        $module->folder_file_upload = sys_get_temp_dir() . '/br-missing-' . uniqid('', true);

        $this->assertFalse($this->callPrivate($module, 'folderUploadFilesHasGoodRights'));
    }

    private function moduleWithBlocks(array $blocks)
    {
        $module = $this->createModule();
        $repository = new \InMemoryBlockRepository();
        $repository->activeBlocks = $blocks;
        $module->services['block_reassurance_repository'] = $repository;

        return $module;
    }

    private function moduleWithRepositoryBlocks(array $blocks)
    {
        $module = $this->createModule();
        $repository = new \InMemoryBlockRepository();
        $repository->allBlocks = $blocks;
        $module->services['block_reassurance_repository'] = $repository;

        return $module;
    }
}
