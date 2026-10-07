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

class AdminBlockListingControllerTest extends ModuleTestCase
{
    /**
     * @dataProvider authorizedHooks
     */
    public function testHookConfigurationKeysAreLimitedToTheModuleConstants($hook, $expected)
    {
        $controller = $this->controller();

        $this->assertSame($expected, $this->callPrivate($controller, 'isAuthorizedHookConfigurationKey', [$hook]));
    }

    public static function authorizedHooks()
    {
        return [
            'header' => [\blockreassurance::PSR_HOOK_HEADER, true],
            'footer' => [\blockreassurance::PSR_HOOK_FOOTER, true],
            'product' => [\blockreassurance::PSR_HOOK_PRODUCT, true],
            'checkout' => [\blockreassurance::PSR_HOOK_CHECKOUT, true],
            'wrong case' => ['psr_hook_header', false],
            'empty' => ['', false],
            'unknown' => ['PSR_ICON_COLOR', false],
        ];
    }

    /**
     * @dataProvider positionValues
     */
    public function testPositionValuesAreLimitedToNoneBelowAndAbove($value, $expected)
    {
        $controller = $this->controller();

        $this->assertSame($expected, $this->callPrivate($controller, 'isAuthorizedPositionValue', [$value]));
    }

    public static function positionValues()
    {
        return [
            'none' => ['0', true],
            'below' => [1, true],
            'above' => ['2', true],
            'truncated decimal' => ['1.9', true],
            'padded' => [' 2 ', true],
            'out of range' => ['3', false],
            'negative' => ['-1', false],
            'missing boolean becomes none' => [false, true],
            'empty string becomes none' => ['', true],
        ];
    }

    public function testChangeBlockStatusTogglesAndStampsTheUpdateTime()
    {
        \Tools::$values = ['idpsr' => '8abc', 'status' => '1'];
        $controller = $this->controller();
        $before = time();

        $controller->displayAjaxChangeBlockStatus();

        $update = \Db::getInstance()->updates[0];
        $this->assertSame('psreassurance', $update['table']);
        $this->assertSame('id_psreassurance = 8', $update['where']);
        $this->assertSame(0, $update['data']['status']);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $update['data']['date_upd']);
        $this->assertLessThan(5, abs(strtotime($update['data']['date_upd']) - $before));
        $this->assertSame('"success"', $controller->ajaxOutput);
    }

    public function testChangeBlockStatusTurnsEveryNonEnabledValueOn()
    {
        \Tools::$values = ['idpsr' => 4, 'status' => '0'];
        $controller = $this->controller();

        $controller->displayAjaxChangeBlockStatus();

        $this->assertSame(1, \Db::getInstance()->updates[0]['data']['status']);
        $this->assertSame('id_psreassurance = 4', \Db::getInstance()->updates[0]['where']);
    }

    public function testChangeBlockStatusEnablesABlockWhenStatusIsMissing()
    {
        \Tools::$values = ['idpsr' => 4];
        $controller = $this->controller();

        $controller->displayAjaxChangeBlockStatus();

        $this->assertSame(1, \Db::getInstance()->updates[0]['data']['status']);
    }

    public function testChangeBlockStatusReportsAFailedUpdate()
    {
        \Tools::$values = ['idpsr' => 4, 'status' => 1];
        \Db::getInstance()->updateResult = false;
        $controller = $this->controller();

        $controller->displayAjaxChangeBlockStatus();

        $this->assertSame('"error"', $controller->ajaxOutput);
    }

    public function testDeleteBlockRemovesTranslationsBeforeTheBlock()
    {
        \Tools::$values = ['idBlock' => '5'];
        \Db::getInstance()->getRowResult = [
            'id_psreassurance' => 5,
            'custom_icon' => '',
        ];
        $controller = $this->controller();

        $controller->displayAjaxDeleteBlock();

        $this->assertSame([
            ['table' => 'psreassurance_lang', 'where' => 'id_psreassurance = 5'],
            ['table' => 'psreassurance', 'where' => 'id_psreassurance = 5'],
        ], \Db::getInstance()->deletes);
        $this->assertSame('"success"', $controller->ajaxOutput);
    }

    public function testDeleteBlockRemovesOnlyTheBasenameOfACustomIcon()
    {
        $directory = _PS_ROOT_DIR_ . '/modules/blockreassurance/views/img/img_perso';
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        $icon = $directory . '/secret.svg';
        $decoy = dirname($directory) . '/secret.svg';
        file_put_contents($icon, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        file_put_contents($decoy, 'keep');
        \Tools::$values = ['idBlock' => 9];
        \Db::getInstance()->getRowResult = [
            'id_psreassurance' => 9,
            'custom_icon' => '../../secret.svg',
        ];
        $controller = $this->controller();
        $controller->module = $this->createModule();

        try {
            $controller->displayAjaxDeleteBlock();

            $this->assertFileDoesNotExist($icon);
            $this->assertFileExists($decoy);
            $this->assertCount(2, \Db::getInstance()->deletes);
            $this->assertSame('"success"', $controller->ajaxOutput);
        } finally {
            if (file_exists($icon)) {
                unlink($icon);
            }
            if (file_exists($decoy)) {
                unlink($decoy);
            }
        }
    }

    public function testDeleteBlockContinuesWhenTheCustomIconFileIsAlreadyGone()
    {
        \Tools::$values = ['idBlock' => 9];
        \Db::getInstance()->getRowResult = [
            'id_psreassurance' => 9,
            'custom_icon' => 'missing.svg',
        ];
        $controller = $this->controller();
        $controller->module = $this->createModule();

        $controller->displayAjaxDeleteBlock();

        $this->assertCount(2, \Db::getInstance()->deletes);
        $this->assertSame('"success"', $controller->ajaxOutput);
    }

    public function testDeleteBlockStopsWhenRemovingTranslationsFails()
    {
        \Tools::$values = ['idBlock' => 9];
        \Db::getInstance()->getRowResult = ['id_psreassurance' => 9, 'custom_icon' => ''];
        \Db::getInstance()->deleteResult = false;
        $controller = $this->controller();

        $controller->displayAjaxDeleteBlock();

        $this->assertCount(1, \Db::getInstance()->deletes);
        $this->assertSame('psreassurance_lang', \Db::getInstance()->deletes[0]['table']);
        $this->assertSame('"error"', $controller->ajaxOutput);
    }

    public function testDeleteBlockReportsAnUnknownBlock()
    {
        \Tools::$values = ['idBlock' => 404];
        \Db::getInstance()->getRowResult = false;
        $controller = $this->controller();

        $controller->displayAjaxDeleteBlock();

        $this->assertSame([], \Db::getInstance()->deletes);
        $this->assertSame('"error"', $controller->ajaxOutput);
    }

    public function testSavePositionByHookAcceptsKnownPairsOnly()
    {
        \Tools::$values = [
            'hook' => \blockreassurance::PSR_HOOK_PRODUCT,
            'value' => '1',
        ];
        $controller = $this->controller();

        $controller->displayAjaxSavePositionByHook();

        $this->assertSame([['PSR_HOOK_PRODUCT', '1']], \Configuration::$updates);
        $this->assertSame('"success"', $controller->ajaxOutput);
    }

    public function testSavePositionByHookRejectsAnUnknownHook()
    {
        \Tools::$values = ['hook' => 'PSR_ICON_COLOR', 'value' => '1'];
        $controller = $this->controller();

        $controller->displayAjaxSavePositionByHook();

        $this->assertSame([], \Configuration::$updates);
        $this->assertSame('"error"', $controller->ajaxOutput);
    }

    public function testSavePositionByHookRejectsAnOutOfRangePosition()
    {
        \Tools::$values = ['hook' => \blockreassurance::PSR_HOOK_HEADER, 'value' => '3'];
        $controller = $this->controller();

        $controller->displayAjaxSavePositionByHook();

        $this->assertSame([], \Configuration::$updates);
        $this->assertSame('"error"', $controller->ajaxOutput);
    }

    public function testSavePositionByHookStoresAMissingValueAsNone()
    {
        \Tools::$values = ['hook' => \blockreassurance::PSR_HOOK_FOOTER];
        $controller = $this->controller();

        $controller->displayAjaxSavePositionByHook();

        $this->assertSame([['PSR_HOOK_FOOTER', false]], \Configuration::$updates);
        $this->assertSame('"success"', $controller->ajaxOutput);
    }

    public function testSaveColorRequiresBothColors()
    {
        \Tools::$values = ['color1' => '#112233', 'color2' => ''];
        $controller = $this->controller();

        $controller->displayAjaxSaveColor();

        $this->assertSame([], \Configuration::$updates);
        $this->assertSame('"error"', $controller->ajaxOutput);
    }

    public function testSaveColorRejectsAZeroStringBecauseEmptyTreatsItAsBlank()
    {
        \Tools::$values = ['color1' => '0', 'color2' => '#000000'];
        $controller = $this->controller();

        $controller->displayAjaxSaveColor();

        $this->assertSame([], \Configuration::$updates);
        $this->assertSame('"error"', $controller->ajaxOutput);
    }

    public function testSaveColorWritesIconThenText()
    {
        \Tools::$values = ['color1' => '#abcdef', 'color2' => '#101010'];
        $controller = $this->controller();

        $controller->displayAjaxSaveColor();

        $this->assertSame([
            ['PSR_ICON_COLOR', '#abcdef'],
            ['PSR_TEXT_COLOR', '#101010'],
        ], \Configuration::$updates);
        $this->assertSame('"success"', $controller->ajaxOutput);
    }

    public function testSaveColorStopsWhenTheIconUpdateFails()
    {
        \Tools::$values = ['color1' => '#abcdef', 'color2' => '#101010'];
        \Configuration::$failKeys = ['PSR_ICON_COLOR'];
        $controller = $this->controller();

        $controller->displayAjaxSaveColor();

        $this->assertSame([['PSR_ICON_COLOR', '#abcdef']], \Configuration::$updates);
        $this->assertSame('"error"', $controller->ajaxOutput);
    }

    public function testUpdatePositionNumbersBlocksFromOneAndStopsOnFailure()
    {
        \Tools::$values = ['blocks' => ['10', '20abc', 30]];
        \Db::getInstance()->updateResults = [true, false, true];
        $controller = $this->controller();

        $controller->displayAjaxUpdatePosition();

        $this->assertSame([
            ['table' => 'psreassurance', 'data' => ['position' => 1], 'where' => 'id_psreassurance = 10'],
            ['table' => 'psreassurance', 'data' => ['position' => 2], 'where' => 'id_psreassurance = 20'],
        ], \Db::getInstance()->updates);
        $this->assertSame('"error"', $controller->ajaxOutput);
    }

    public function testUpdatePositionUsesArrayKeysAsThePositionSource()
    {
        \Tools::$values = ['blocks' => [2 => 9, 5 => 8]];
        $controller = $this->controller();

        $controller->displayAjaxUpdatePosition();

        $this->assertSame(3, \Db::getInstance()->updates[0]['data']['position']);
        $this->assertSame('id_psreassurance = 9', \Db::getInstance()->updates[0]['where']);
        $this->assertSame(6, \Db::getInstance()->updates[1]['data']['position']);
        $this->assertSame('"success"', $controller->ajaxOutput);
    }

    public function testUpdatePositionRejectsAnEmptyOrScalarPayload()
    {
        $controller = $this->controller();
        \Tools::$values = ['blocks' => []];
        $controller->displayAjaxUpdatePosition();
        $this->assertSame('"error"', $controller->ajaxOutput);

        \Tools::$values = ['blocks' => '1,2,3'];
        $controller->displayAjaxUpdatePosition();
        $this->assertSame([], \Db::getInstance()->updates);
        $this->assertSame('"error"', $controller->ajaxOutput);
    }

    public function testSaveBlockContentRejectsAnUnsupportedExtensionBeforeTouchingServices()
    {
        \Tools::$values = [
            'picto' => 'icons/file.bmp',
            'id_block' => 1,
            'lang_values' => '{}',
        ];
        $controller = $this->controller();

        $controller->displayAjaxSaveBlockContent();

        $this->assertSame('"error"', $controller->ajaxOutput);
        $this->assertSame([], \Db::getInstance()->selects);
    }

    public function testSaveBlockContentRejectsAnExtensionThatOnlyDiffersByCase()
    {
        \Tools::$values = ['picto' => 'icons/file.PNG', 'lang_values' => '[]'];
        $controller = $this->controller();

        $controller->displayAjaxSaveBlockContent();

        $this->assertSame('"error"', $controller->ajaxOutput);
    }

    public function testSaveBlockContentCreatesAnInactiveBlockAfterTheCurrentMaxPosition()
    {
        \Db::getInstance()->getValueResult = '7';
        \Tools::$values = $this->blockPayload([
            'picto' => '/img/reassurance/pack2/security.svg',
            'id_block' => '',
            'typelink' => '2',
            'id_cms' => '4',
        ]);
        $handler = new \FakeFormDataHandler();
        $finder = new \FakeBlockFinder();
        $controller = $this->controllerWithServices($finder, $handler);

        $controller->displayAjaxSaveBlockContent();

        $this->assertSame([], $handler->updated);
        $this->assertCount(1, $handler->created);
        /** @var Psreassurance $block */
        $block = $handler->created[0][0];
        $this->assertSame(8, $block->getPosition());
        $this->assertSame(0, $block->getStatus());
        $this->assertSame('reassurance/pack2/security.svg', $block->getIcon());
        $this->assertSame('', $block->getCustomIcon());
        $this->assertInstanceOf(\DateTime::class, $block->getDateAdd());
        $this->assertSame('UTC', $block->getDateAdd()->getTimezone()->getName());
        $this->assertSame('UTC', $block->getDateUpd()->getTimezone()->getName());
        $this->assertSame(2, $handler->created[0][2]);
        $this->assertSame(4, $handler->created[0][3]);
        $this->assertSame('Security', $handler->created[0][1][1]->title);
        $this->assertSame('"success"', $controller->ajaxOutput);
    }

    public function testSaveBlockContentStartsPositionAtOneWhenTheTableIsEmpty()
    {
        \Db::getInstance()->getValueResult = null;
        \Tools::$values = $this->blockPayload(['picto' => '', 'id_block' => 0]);
        $handler = new \FakeFormDataHandler();
        $controller = $this->controllerWithServices(new \FakeBlockFinder(), $handler);

        $controller->displayAjaxSaveBlockContent();

        $this->assertSame(1, $handler->created[0][0]->getPosition());
        $this->assertSame('', $handler->created[0][0]->getIcon());
        $this->assertSame('', $handler->created[0][0]->getCustomIcon());
    }

    public function testSaveBlockContentStoresACustomIconBasename()
    {
        $module = $this->createModule();
        \Tools::$values = $this->blockPayload([
            'picto' => $module->img_path_perso . '/nested/mine.svg',
            'id_block' => '0',
        ]);
        $handler = new \FakeFormDataHandler();
        $controller = $this->controllerWithServices(new \FakeBlockFinder(), $handler);
        $controller->module = $module;

        $controller->displayAjaxSaveBlockContent();

        $this->assertSame('', $handler->created[0][0]->getIcon());
        $this->assertSame('mine.svg', $handler->created[0][0]->getCustomIcon());
    }

    public function testSaveBlockContentUpdatesAnExistingBlock()
    {
        $existing = new Psreassurance();
        $existing->setIcon('old.svg')->setCustomIcon('')->setStatus(1)->setPosition(2);
        $finder = new \FakeBlockFinder();
        $finder->found = $existing;
        \Tools::$values = $this->blockPayload([
            'picto' => 'a/b/c/reassurance/pack1/lock.svg',
            'id_block' => '12',
            'typelink' => '1',
            'id_cms' => '9',
        ]);
        $handler = new \FakeFormDataHandler();
        $controller = $this->controllerWithServices($finder, $handler);

        $controller->displayAjaxSaveBlockContent();

        $this->assertSame([], $handler->created);
        $this->assertSame($existing, $handler->updated[0][0]);
        $this->assertSame('reassurance/pack1/lock.svg', $existing->getIcon());
        $this->assertSame('', $existing->getCustomIcon());
        $this->assertSame(1, $handler->updated[0][2]);
        $this->assertSame(9, $handler->updated[0][3]);
        $this->assertSame('"success"', $controller->ajaxOutput);
    }

    public function testSaveBlockContentKeepsFewerThanThreeIconSegments()
    {
        $existing = new Psreassurance();
        $finder = new \FakeBlockFinder();
        $finder->found = $existing;
        \Tools::$values = $this->blockPayload([
            'picto' => 'pack2/security.svg',
            'id_block' => 3,
        ]);
        $handler = new \FakeFormDataHandler();
        $controller = $this->controllerWithServices($finder, $handler);

        $controller->displayAjaxSaveBlockContent();

        $this->assertSame('pack2/security.svg', $existing->getIcon());
    }

    public function testSaveBlockContentFailsWhenTheUploadedFileIsRejected()
    {
        \ImageManager::$validateUploadResult = 'Image format not recognized';
        $_FILES = [
            'file' => [
                'name' => 'note.txt',
                'tmp_name' => '/tmp/not-uploaded',
                'type' => 'text/plain',
                'error' => 0,
                'size' => 4,
            ],
        ];
        $existing = new Psreassurance();
        $finder = new \FakeBlockFinder();
        $finder->found = $existing;
        \Tools::$values = $this->blockPayload(['picto' => 'pack/icon.svg', 'id_block' => 2]);
        $handler = new \FakeFormDataHandler();
        $controller = $this->controllerWithServices($finder, $handler);

        $controller->displayAjaxSaveBlockContent();

        $this->assertSame([], $handler->updated);
        $this->assertSame([], $handler->created);
        $this->assertSame('"error"', $controller->ajaxOutput);
    }

    public function testSaveBlockContentReplacesTheCustomIconWithTheUploadedFile()
    {
        $uploadDirectory = sys_get_temp_dir() . '/br-upload-file-' . uniqid('', true);
        mkdir($uploadDirectory, 0777, true);
        $oldIcon = $uploadDirectory . '/old.svg';
        file_put_contents($oldIcon, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        $source = $uploadDirectory . '/incoming.svg';
        file_put_contents($source, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        \ImageManager::$validateUploadResult = false;
        $_FILES = [
            'file' => [
                'name' => 'incoming.svg',
                'tmp_name' => $source,
                'type' => 'image/svg+xml',
                'error' => 0,
                'size' => 10,
            ],
        ];
        $existing = new Psreassurance();
        $existing->setCustomIcon('previous.svg')->setIcon('stock.svg');
        $finder = new \FakeBlockFinder();
        $finder->found = $existing;
        $module = $this->createModule();
        $module->folder_file_upload = $uploadDirectory . '/';
        \Tools::$values = $this->blockPayload([
            'picto' => $module->img_path_perso . '/old.svg',
            'id_block' => 2,
        ]);
        $handler = new \FakeFormDataHandler();
        $controller = $this->controllerWithServices($finder, $handler);
        $controller->module = $module;
        \blockreassurance::$static_folder_file_upload = $uploadDirectory;

        try {
            $controller->displayAjaxSaveBlockContent();

            $this->assertFileDoesNotExist($oldIcon);
            $this->assertSame('incoming.svg', $existing->getCustomIcon());
            $this->assertSame('', $existing->getIcon());
            $this->assertSame($existing, $handler->updated[0][0]);
            $this->assertSame('"success"', $controller->ajaxOutput);
        } finally {
            if (is_dir($uploadDirectory)) {
                foreach (scandir($uploadDirectory) as $entry) {
                    if ('.' !== $entry && '..' !== $entry && is_file($uploadDirectory . '/' . $entry)) {
                        unlink($uploadDirectory . '/' . $entry);
                    }
                }
                rmdir($uploadDirectory);
            }
        }
    }

    public function testSaveBlockContentAllowsAnEmptyLanguagePayload()
    {
        \Db::getInstance()->getValueResult = 0;
        \Tools::$values = $this->blockPayload(['picto' => 'a.svg', 'id_block' => 0, 'lang_values' => 'null']);
        $handler = new \FakeFormDataHandler();
        $controller = $this->controllerWithServices(new \FakeBlockFinder(), $handler);

        $controller->displayAjaxSaveBlockContent();

        $this->assertSame([], $handler->created[0][1]);
        $this->assertSame('"success"', $controller->ajaxOutput);
    }

    public function testSaveBlockContentFailsWhenTheBlockCannotBeLoaded()
    {
        $finder = new \FakeBlockFinder();
        $finder->found = null;
        \Tools::$values = $this->blockPayload(['picto' => 'a.svg', 'id_block' => 99]);
        $controller = $this->controllerWithServices($finder, new \FakeFormDataHandler());

        $this->expectException(\Error::class);

        $controller->displayAjaxSaveBlockContent();
    }

    private function controller()
    {
        return new \AdminBlockListingController();
    }

    private function controllerWithServices($finder, \FakeFormDataHandler $handler)
    {
        $container = new \FakeServiceContainer();
        $container->services['block_reassurance_repository'] = $finder;
        $container->services['block_reassurance_form_data_handler'] = $handler;
        $controller = $this->controller();
        $controller->context->controller->container = $container;
        $controller->module = $this->createModule();

        return $controller;
    }

    private function blockPayload(array $overrides)
    {
        return array_merge([
            'picto' => 'reassurance/pack2/security.svg',
            'id_block' => 0,
            'typelink' => '0',
            'id_cms' => '0',
            'lang_values' => json_encode([
                1 => ['title' => 'Security', 'description' => 'Encrypted', 'url' => 'https://example.test'],
            ]),
        ], $overrides);
    }
}
