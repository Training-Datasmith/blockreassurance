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

use Doctrine\Common\Collections\ArrayCollection;
use PrestaShop\Module\BlockReassurance\Entity\Psreassurance;
use PrestaShop\Module\BlockReassurance\Entity\PsreassuranceLang;
use PrestaShop\Module\BlockReassurance\Tests\ModuleTestCase;
use PrestaShopBundle\Entity\Lang;

class PsreassuranceTest extends ModuleTestCase
{
    public function testLinkTypeConstantsMatchTheLegacyActivityModel()
    {
        $this->assertSame(0, Psreassurance::TYPE_LINK_NONE);
        $this->assertSame(1, Psreassurance::TYPE_LINK_CMS_PAGE);
        $this->assertSame(2, Psreassurance::TYPE_LINK_URL);
        $this->assertSame(\ReassuranceActivity::TYPE_LINK_NONE, Psreassurance::TYPE_LINK_NONE);
        $this->assertSame(\ReassuranceActivity::TYPE_LINK_CMS_PAGE, Psreassurance::TYPE_LINK_CMS_PAGE);
        $this->assertSame(\ReassuranceActivity::TYPE_LINK_URL, Psreassurance::TYPE_LINK_URL);
    }

    public function testNewBlockStartsWithAnEmptyTranslationCollection()
    {
        $block = new Psreassurance();

        $this->assertInstanceOf(ArrayCollection::class, $block->getPsreassuranceLangs());
        $this->assertCount(0, $block->getPsreassuranceLangs());
        $this->assertSame('', $block->getPsreassuranceTitle());
        $this->assertSame('', $block->getPsreassuranceDescription());
        $this->assertNull($block->getPsreassuranceLangByLangId(1));
    }

    public function testSettersAreFluentAndRoundTripScalarFields()
    {
        $block = new Psreassurance();
        $added = new \DateTime('2020-01-02 03:04:05', new \DateTimeZone('UTC'));
        $updated = new \DateTime('2021-06-07 08:09:10', new \DateTimeZone('UTC'));

        $this->assertSame($block, $block->setIcon('reassurance/pack2/security.svg'));
        $block
            ->setCustomIcon('mine.svg')
            ->setStatus(1)
            ->setPosition(4)
            ->setLinkType(Psreassurance::TYPE_LINK_URL)
            ->setCmsId(12)
            ->setDateAdd($added)
            ->setDateUpd($updated);

        $this->assertSame('reassurance/pack2/security.svg', $block->getIcon());
        $this->assertSame('mine.svg', $block->getCustomIcon());
        $this->assertSame(1, $block->getStatus());
        $this->assertSame(4, $block->getPosition());
        $this->assertSame(Psreassurance::TYPE_LINK_URL, $block->getLinkType());
        $this->assertSame(12, $block->getCmsId());
        $this->assertSame($added, $block->getDateAdd());
        $this->assertSame($updated, $block->getDateUpd());
    }

    public function testIdentifierCanBeReadAfterThePersistenceLayerAssignsIt()
    {
        $block = new Psreassurance();
        $this->setPrivateProperty($block, 'id', 27);

        $this->assertSame(27, $block->getId());
    }

    public function testTitleAndDescriptionComeFromTheFirstTranslationOnly()
    {
        $block = new Psreassurance();
        $french = $this->translation(2, 'Livraison', 'Offerte');
        $english = $this->translation(1, 'Delivery', 'Free');

        $block->addPsreassuranceLang($french);
        $block->addPsreassuranceLang($english);

        $this->assertSame('Livraison', $block->getPsreassuranceTitle());
        $this->assertSame('Offerte', $block->getPsreassuranceDescription());
        $this->assertCount(2, $block->getPsreassuranceLangs());
    }

    public function testEmptyStringsAreKeptAsTitlesAndDescriptions()
    {
        $block = new Psreassurance();
        $block->addPsreassuranceLang($this->translation(1, '', ''));

        $this->assertSame('', $block->getPsreassuranceTitle());
        $this->assertSame('', $block->getPsreassuranceDescription());
    }

    public function testLanguageLookupUsesStrictIdentifierComparison()
    {
        $block = new Psreassurance();
        $block->addPsreassuranceLang($this->translation(1, 'One', 'First'));
        $block->addPsreassuranceLang($this->translation(0, 'Zero', 'Origin'));
        $stringKeyed = new PsreassuranceLang();
        $stringKeyed->setLang(new Lang('2'))->setTitle('String')->setDescription('Id')->setLink('');
        $block->addPsreassuranceLang($stringKeyed);

        $this->assertSame('One', $block->getPsreassuranceLangByLangId(1)->getTitle());
        $this->assertSame('Zero', $block->getPsreassuranceLangByLangId(0)->getTitle());
        $this->assertNull($block->getPsreassuranceLangByLangId(2));
        $this->assertNull($block->getPsreassuranceLangByLangId(9));
    }

    public function testAddingATranslationWiresBothSidesOfTheAssociation()
    {
        $block = new Psreassurance();
        $translation = $this->translation(3, 'Returns', '30 days');

        $this->assertSame($block, $block->addPsreassuranceLang($translation));
        $this->assertSame($block, $translation->getPsreassurance());
        $this->assertSame($translation, $block->getPsreassuranceLangByLangId(3));
    }

    private function translation($langId, $title, $description)
    {
        $translation = new PsreassuranceLang();
        $translation
            ->setLang(new Lang($langId))
            ->setTitle($title)
            ->setDescription($description)
            ->setLink('');

        return $translation;
    }
}
