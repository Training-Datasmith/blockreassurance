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
use PrestaShop\Module\BlockReassurance\Entity\PsreassuranceLang;
use PrestaShop\Module\BlockReassurance\Tests\ModuleTestCase;
use PrestaShopBundle\Entity\Lang;

class PsreassuranceLangTest extends ModuleTestCase
{
    public function testLanguageFieldsRoundTrip()
    {
        $block = new Psreassurance();
        $lang = new Lang(5);
        $translation = new PsreassuranceLang();

        $this->assertSame($translation, $translation->setPsreassurance($block));
        $translation
            ->setLang($lang)
            ->setTitle('Security policy')
            ->setDescription('<strong>Payments</strong> are encrypted')
            ->setLink('https://example.test/security');

        $this->assertSame($block, $translation->getPsreassurance());
        $this->assertSame($lang, $translation->getLang());
        $this->assertSame(5, $translation->getLang()->getId());
        $this->assertSame('Security policy', $translation->getTitle());
        $this->assertSame('<strong>Payments</strong> are encrypted', $translation->getDescription());
        $this->assertSame('https://example.test/security', $translation->getLink());
    }

    public function testLinkCanBeClearedToAnEmptyString()
    {
        $translation = new PsreassuranceLang();
        $translation->setLink('https://example.test')->setLink('');

        $this->assertSame('', $translation->getLink());
    }
}
