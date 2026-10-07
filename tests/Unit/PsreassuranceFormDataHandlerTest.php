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

use Doctrine\ORM\EntityManagerInterface;
use PrestaShop\Module\BlockReassurance\Entity\Psreassurance;
use PrestaShop\Module\BlockReassurance\Entity\PsreassuranceLang;
use PrestaShop\Module\BlockReassurance\Form\PsreassuranceFormDataHandler;
use PrestaShop\Module\BlockReassurance\Repository\PsreassuranceRepository;
use PrestaShop\Module\BlockReassurance\Tests\ModuleTestCase;
use PrestaShopBundle\Entity\Lang;
use PrestaShopBundle\Entity\Repository\LangRepository;

class PsreassuranceFormDataHandlerTest extends ModuleTestCase
{
    public function testCreateLangsStoresSubmittedUrlsForEveryLanguage()
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $block = new Psreassurance();
        $handler = $this->handler($entityManager, $this->langRepository());
        $languages = [
            1 => $this->content('Security', 'Encrypted', 'https://example.test/en'),
            2 => $this->content('Sécurité', 'Chiffré', 'https://example.test/fr'),
        ];

        $entityManager->expects($this->once())->method('persist')->with($this->identicalTo($block));
        $entityManager->expects($this->once())->method('flush');

        $handler->createLangs($block, $languages, Psreassurance::TYPE_LINK_URL, 0);

        $this->assertSame(Psreassurance::TYPE_LINK_URL, $block->getLinkType());
        $this->assertCount(2, $block->getPsreassuranceLangs());
        $this->assertSame('Security', $block->getPsreassuranceLangByLangId(1)->getTitle());
        $this->assertSame('Encrypted', $block->getPsreassuranceLangByLangId(1)->getDescription());
        $this->assertSame('https://example.test/en', $block->getPsreassuranceLangByLangId(1)->getLink());
        $this->assertSame('https://example.test/fr', $block->getPsreassuranceLangByLangId(2)->getLink());
        $this->assertSame($block, $block->getPsreassuranceLangByLangId(1)->getPsreassurance());
        $this->assertSame([], \Context::getContext()->link->cmsLinks);
    }

    public function testCreateLangsClearsTheSubmittedUrlWhenTheLinkTypeIsNone()
    {
        $block = new Psreassurance();
        $handler = $this->handler($this->createMock(EntityManagerInterface::class), $this->langRepository());

        $handler->createLangs(
            $block,
            [1 => $this->content('Title', 'Body', 'https://example.test/kept')],
            Psreassurance::TYPE_LINK_NONE,
            0
        );

        $this->assertSame(Psreassurance::TYPE_LINK_NONE, $block->getLinkType());
        $this->assertSame('', $block->getPsreassuranceLangByLangId(1)->getLink());
    }

    public function testCreateLangsReplacesLinksWithTheCmsPageForEverySubmittedLanguage()
    {
        $block = new Psreassurance();
        $handler = $this->handler($this->createMock(EntityManagerInterface::class), $this->langRepository());

        $handler->createLangs(
            $block,
            [
                1 => $this->content('Policy', 'Read me', 'https://example.test/ignore'),
                3 => $this->content('Politique', 'Lire', 'https://example.test/ignore-fr'),
            ],
            Psreassurance::TYPE_LINK_CMS_PAGE,
            15
        );

        $this->assertSame(15, $block->getCmsId());
        $this->assertSame(Psreassurance::TYPE_LINK_CMS_PAGE, $block->getLinkType());
        $this->assertSame('cms/15/lang/1', $block->getPsreassuranceLangByLangId(1)->getLink());
        $this->assertSame('cms/15/lang/3', $block->getPsreassuranceLangByLangId(3)->getLink());
        $this->assertSame([
            ['cms' => 15, 'alias' => null, 'ssl' => null, 'id_lang' => 1],
            ['cms' => 15, 'alias' => null, 'ssl' => null, 'id_lang' => 3],
        ], \Context::getContext()->link->cmsLinks);
    }

    public function testCreateLangsIgnoresAnEmptyCmsId()
    {
        $block = new Psreassurance();
        $handler = $this->handler($this->createMock(EntityManagerInterface::class), $this->langRepository());

        $handler->createLangs(
            $block,
            [1 => $this->content('Policy', 'Read me', 'https://example.test/manual')],
            Psreassurance::TYPE_LINK_CMS_PAGE,
            0
        );

        $this->assertSame('', $block->getPsreassuranceLangByLangId(1)->getLink());
        $this->assertSame([], \Context::getContext()->link->cmsLinks);
    }

    public function testCreateLangsMapsTheUndefinedSentinelToNoLink()
    {
        $block = new Psreassurance();
        $handler = $this->handler($this->createMock(EntityManagerInterface::class), $this->langRepository());

        $handler->createLangs(
            $block,
            [1 => $this->content('Title', 'Body', 'https://example.test')],
            'undefined',
            9
        );

        $this->assertSame(Psreassurance::TYPE_LINK_NONE, $block->getLinkType());
        $this->assertSame([], \Context::getContext()->link->cmsLinks);
    }

    public function testCreateLangsRejectsANumericStringLinkType()
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->never())->method('persist');
        $block = new Psreassurance();
        $handler = $this->handler($entityManager, $this->langRepository());

        $this->expectException(\TypeError::class);

        $handler->createLangs(
            $block,
            [1 => $this->content('Title', 'Body', 'https://example.test')],
            '2',
            0
        );
    }

    public function testCreateLangsSkipsALanguageThatDoesNotExist()
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $langRepository = $this->createMock(LangRepository::class);
        $langRepository->method('find')->willReturn(null);
        $block = new Psreassurance();
        $handler = $this->handler($entityManager, $langRepository);

        $entityManager->expects($this->once())->method('persist')->with($this->identicalTo($block));
        $entityManager->expects($this->once())->method('flush');

        $handler->createLangs(
            $block,
            [1 => $this->content('Title', 'Body', '')],
            Psreassurance::TYPE_LINK_NONE,
            0
        );

        $this->assertCount(0, $block->getPsreassuranceLangs());
    }

    public function testUpdateLangsChangesExistingTranslationsAndSkipsMissingOnes()
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $block = new Psreassurance();
        $existing = new PsreassuranceLang();
        $existing->setLang(new Lang(1))->setTitle('Old')->setDescription('Old body')->setLink('https://old.test');
        $block->addPsreassuranceLang($existing);
        $handler = $this->handler($entityManager, $this->createMock(LangRepository::class));

        $entityManager->expects($this->once())->method('persist')->with($this->identicalTo($block));
        $entityManager->expects($this->once())->method('flush');

        $handler->updateLangs(
            $block,
            [
                1 => $this->content('New', 'New body', 'https://new.test'),
                2 => $this->content('Absent', 'Skipped', 'https://skipped.test'),
            ],
            Psreassurance::TYPE_LINK_URL,
            0
        );

        $this->assertSame(Psreassurance::TYPE_LINK_URL, $block->getLinkType());
        $this->assertCount(1, $block->getPsreassuranceLangs());
        $this->assertSame('New', $existing->getTitle());
        $this->assertSame('New body', $existing->getDescription());
        $this->assertSame('https://new.test', $existing->getLink());
        $this->assertNull($block->getPsreassuranceLangByLangId(2));
    }

    public function testUpdateLangsStillPersistsWhenEveryLanguageIsMissing()
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $block = new Psreassurance();
        $handler = $this->handler($entityManager, $this->createMock(LangRepository::class));
        $entityManager->expects($this->once())->method('persist');
        $entityManager->expects($this->once())->method('flush');

        $handler->updateLangs(
            $block,
            [8 => $this->content('Nope', 'Missing', '')],
            'undefined',
            0
        );

        $this->assertSame(Psreassurance::TYPE_LINK_NONE, $block->getLinkType());
        $this->assertCount(0, $block->getPsreassuranceLangs());
    }

    public function testUpdateLangsOverwritesLinksFromTheCmsPage()
    {
        $block = new Psreassurance();
        $existing = new PsreassuranceLang();
        $existing->setLang(new Lang(2))->setTitle('Old')->setDescription('Old')->setLink('https://old.test');
        $block->addPsreassuranceLang($existing);
        $handler = $this->handler($this->createMock(EntityManagerInterface::class), $this->createMock(LangRepository::class));

        $handler->updateLangs(
            $block,
            [2 => $this->content('Policy', 'Body', 'https://example.test/ignore')],
            Psreassurance::TYPE_LINK_CMS_PAGE,
            6
        );

        $this->assertSame(6, $block->getCmsId());
        $this->assertSame('cms/6/lang/2', $existing->getLink());
        $this->assertSame('Policy', $existing->getTitle());
    }

    public function testUpdateLangsDoesNotNeedTheLanguageRepository()
    {
        $langRepository = $this->createMock(LangRepository::class);
        $langRepository->method('find')->willReturn(null);
        $block = new Psreassurance();
        $existing = new PsreassuranceLang();
        $existing->setLang(new Lang(1))->setTitle('Old')->setDescription('Old')->setLink('');
        $block->addPsreassuranceLang($existing);
        $handler = $this->handler($this->createMock(EntityManagerInterface::class), $langRepository);

        $handler->updateLangs(
            $block,
            [1 => $this->content('Kept', 'Still here', 'https://example.test')],
            Psreassurance::TYPE_LINK_NONE,
            0
        );

        $this->assertSame('Kept', $existing->getTitle());
        $this->assertSame('', $existing->getLink());
    }

    private function handler(EntityManagerInterface $entityManager, LangRepository $langRepository)
    {
        $repository = $this->createMock(PsreassuranceRepository::class);

        return new PsreassuranceFormDataHandler($repository, $langRepository, $entityManager);
    }

    private function langRepository()
    {
        $langRepository = $this->createMock(LangRepository::class);
        $langRepository->method('find')->willReturnCallback(function ($id) {
            return new Lang($id);
        });

        return $langRepository;
    }

    private function content($title, $description, $url)
    {
        $content = new \stdClass();
        $content->title = $title;
        $content->description = $description;
        $content->url = $url;

        return $content;
    }
}
