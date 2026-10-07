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

class ReassuranceActivityTest extends ModuleTestCase
{
    private $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = _PS_ROOT_DIR_ . '/activity-' . getmypid();
        if (!is_dir($this->root)) {
            mkdir($this->root, 0777, true);
        }
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->root);
        parent::tearDown();
    }

    public function testObjectModelDefinitionDescribesTheMultilangTable()
    {
        $definition = \ReassuranceActivity::$definition;

        $this->assertSame('psreassurance', $definition['table']);
        $this->assertSame('id_psreassurance', $definition['primary']);
        $this->assertTrue($definition['multilang']);
        $this->assertTrue($definition['fields']['status']['required']);
        $this->assertTrue($definition['fields']['title']['lang']);
        $this->assertTrue($definition['fields']['description']['lang']);
        $this->assertTrue($definition['fields']['link']['lang']);
        $this->assertFalse($definition['fields']['link']['required']);
        $this->assertSame(255, $definition['fields']['title']['size']);
        $this->assertSame(2000, $definition['fields']['description']['size']);
    }

    public function testHandleBlockValuesCopiesOnlyLanguagesThatExistInTheShop()
    {
        \Language::$languages = [
            ['id_lang' => 1, 'locale' => 'en-US'],
            ['id_lang' => 2, 'locale' => 'fr-FR'],
        ];
        $activity = $this->activity();

        $activity->handleBlockValues(
            [
                1 => $this->content('Security', '<em>Encrypted</em>', 'https://example.test/en'),
                9 => $this->content('Ghost', 'Not installed', 'https://example.test/ghost'),
            ],
            \ReassuranceActivity::TYPE_LINK_URL,
            0
        );

        $this->assertSame(\ReassuranceActivity::TYPE_LINK_URL, $activity->type_link);
        $this->assertSame('Security', $activity->title[1]);
        $this->assertSame('<em>Encrypted</em>', $activity->description[1]);
        $this->assertSame('https://example.test/en', $activity->link[1]);
        $this->assertArrayNotHasKey(2, $activity->title);
        $this->assertArrayNotHasKey(9, $activity->title);
        $this->assertSame([], \Context::getContext()->link->cmsLinks);
    }

    public function testHandleBlockValuesClearsTheLinkUnlessTheTypeIsACustomUrl()
    {
        \Language::$languages = [
            ['id_lang' => 1, 'locale' => 'en-US'],
        ];
        $activity = $this->activity();

        $activity->handleBlockValues(
            [1 => $this->content('Title', 'Body', 'https://example.test/dropped')],
            \ReassuranceActivity::TYPE_LINK_NONE,
            0
        );

        $this->assertSame(\ReassuranceActivity::TYPE_LINK_NONE, $activity->type_link);
        $this->assertSame('', $activity->link[1]);
    }

    public function testHandleBlockValuesBuildsCmsLinksForEveryInstalledLanguage()
    {
        \Language::$languages = [
            ['id_lang' => 1, 'locale' => 'en-US'],
            ['id_lang' => 2, 'locale' => 'fr-FR'],
        ];
        $activity = $this->activity();

        $activity->handleBlockValues(
            [1 => $this->content('Policy', 'Body', 'https://example.test/ignore')],
            \ReassuranceActivity::TYPE_LINK_CMS_PAGE,
            11
        );

        $this->assertSame(11, $activity->id_cms);
        $this->assertSame(\ReassuranceActivity::TYPE_LINK_CMS_PAGE, $activity->type_link);
        $this->assertSame('cms/11/lang/1', $activity->link[1]);
        $this->assertSame('cms/11/lang/2', $activity->link[2]);
        $this->assertArrayNotHasKey(2, $activity->title);
    }

    public function testHandleBlockValuesLeavesLinksEmptyWhenTheCmsIdIsEmpty()
    {
        \Language::$languages = [
            ['id_lang' => 1, 'locale' => 'en-US'],
        ];
        $activity = $this->activity();

        $activity->handleBlockValues(
            [1 => $this->content('Policy', 'Body', 'https://example.test/ignore')],
            \ReassuranceActivity::TYPE_LINK_CMS_PAGE,
            0
        );

        $this->assertSame('', $activity->link[1]);
        $this->assertNull($activity->id_cms);
        $this->assertSame([], \Context::getContext()->link->cmsLinks);
    }

    public function testHandleBlockValuesNormalizesTheUndefinedSentinel()
    {
        \Language::$languages = [
            ['id_lang' => 1, 'locale' => 'en-US'],
        ];
        $activity = $this->activity();

        $activity->handleBlockValues(
            [1 => $this->content('Title', 'Body', 'https://example.test')],
            'undefined',
            0
        );

        $this->assertSame(\ReassuranceActivity::TYPE_LINK_NONE, $activity->type_link);
        $this->assertSame('', $activity->link[1]);
    }

    public function testGetAllBlockGroupsRowsAndPreservesFirstSeenScalars()
    {
        \Db::getInstance()->rows = [
            $this->flatRow(1, 1, 'EN', 'English', '', 'icon-a.svg', '', 1),
            $this->flatRow(1, 2, 'FR', 'French', 'https://fr.test', 'icon-b.svg', 'custom.svg', 1),
            $this->flatRow(3, 1, 'Other', 'Block', '', 'icon-c.svg', '', 0),
        ];

        $result = \ReassuranceActivity::getAllBlock();
        $sql = \Db::getInstance()->selects[0];

        $this->assertStringContainsString('ps_psreassurance', $sql);
        $this->assertStringContainsString('ps_psreassurance_lang', $sql);
        $this->assertStringContainsString('ORDER BY pr.position', $sql);
        $this->assertSame([1, 3], array_keys($result));
        $this->assertSame('icon-a.svg', $result[1]['icon']);
        $this->assertSame('', $result[1]['custom_icon']);
        $this->assertSame([1 => 'EN', 2 => 'FR'], $result[1]['title']);
        $this->assertSame([1 => 'English', 2 => 'French'], $result[1]['description']);
        $this->assertSame([1 => '', 2 => 'https://fr.test'], $result[1]['url']);
        $this->assertSame([1 => 'Other'], $result[3]['title']);
    }

    public function testGetAllBlockByStatusRequestsOnlyActiveRowsForTheLanguage()
    {
        $png = $this->root . '/photo.png';
        $svg = $this->root . '/badge.svg';
        file_put_contents($png, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
        file_put_contents($svg, '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<svg xmlns="http://www.w3.org/2000/svg" width="1" height="1"></svg>');

        \Db::getInstance()->rows = [
            $this->flatRow(1, 2, 'Png', 'Raster', '', '', $this->relative($png), 1),
            $this->flatRow(2, 2, 'Svg', 'Vector', '', '', $this->relative($svg), 1),
            $this->flatRow(3, 2, 'None', 'Plain', '', 'stock.svg', '', 1),
        ];

        $pngMime = \ReassuranceActivity::getMimeType($png);
        $svgMime = \ReassuranceActivity::getMimeType($svg);
        $this->assertSame('image/png', $pngMime);
        $this->assertNotFalse($svgMime);
        $this->assertNotSame('', $svgMime);

        $result = \ReassuranceActivity::getAllBlockByStatus(2);
        $sql = \Db::getInstance()->selects[0];
        $svgMimes = ['image/svg', 'image/svg+xml'];

        $this->assertStringContainsString('pr.status = 1', $sql);
        $this->assertStringContainsString('prl.id_lang = "2"', $sql);
        $this->assertSame(in_array($pngMime, $svgMimes, true), $result[0]['is_svg']);
        $this->assertSame(in_array($svgMime, $svgMimes, true), $result[1]['is_svg']);
        $this->assertFalse($result[2]['is_svg']);
        $this->assertSame('stock.svg', $result[2]['icon']);
    }

    public function testGetAllBlockByStatusCastsTheLanguageIdIntoTheQuery()
    {
        \Db::getInstance()->rows = [];

        \ReassuranceActivity::getAllBlockByStatus('2abc');

        $this->assertStringContainsString('prl.id_lang = "2"', \Db::getInstance()->selects[0]);
    }

    public function testGetAllBlockByStatusDefaultsToLanguageOne()
    {
        \Db::getInstance()->rows = [];

        \ReassuranceActivity::getAllBlockByStatus();

        $this->assertStringContainsString('prl.id_lang = "1"', \Db::getInstance()->selects[0]);
    }

    public function testGetMimeTypeDetectsPngSvgAndUnknownFiles()
    {
        $png = $this->root . '/pixel.png';
        $svg = $this->root . '/icon.svg';
        $empty = $this->root . '/empty.bin';
        file_put_contents($png, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='));
        file_put_contents($svg, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');
        file_put_contents($empty, '');

        $this->assertSame('image/png', \ReassuranceActivity::getMimeType($png));
        $svgMime = \ReassuranceActivity::getMimeType($svg);
        $this->assertNotFalse($svgMime);
        $this->assertNotSame('', $svgMime);
        $emptyMime = \ReassuranceActivity::getMimeType($empty);
        $this->assertNotSame('image/png', $emptyMime);
        $this->assertNotFalse($emptyMime);
    }

    private function activity()
    {
        $activity = new \ReassuranceActivity();
        $activity->title = [];
        $activity->description = [];
        $activity->link = [];

        return $activity;
    }

    private function content($title, $description, $url)
    {
        $content = new \stdClass();
        $content->title = $title;
        $content->description = $description;
        $content->url = $url;

        return $content;
    }

    private function flatRow($id, $langId, $title, $description, $link, $icon, $customIcon, $status)
    {
        return [
            'id_psreassurance' => $id,
            'id_lang' => $langId,
            'title' => $title,
            'description' => $description,
            'link' => $link,
            'icon' => $icon,
            'custom_icon' => $customIcon,
            'status' => $status,
            'position' => $id,
        ];
    }

    private function relative($absolutePath)
    {
        return substr($absolutePath, strlen(_PS_ROOT_DIR_));
    }

    private function removeTree($path)
    {
        if (!file_exists($path)) {
            return;
        }
        if (is_dir($path)) {
            foreach (scandir($path) as $entry) {
                if ('.' === $entry || '..' === $entry) {
                    continue;
                }
                $this->removeTree($path . '/' . $entry);
            }
            rmdir($path);

            return;
        }
        unlink($path);
    }
}
