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
use PrestaShop\Module\BlockReassurance\Repository\PsreassuranceRepository;
use PrestaShop\Module\BlockReassurance\Tests\ModuleTestCase;

class PsreassuranceRepositoryTest extends ModuleTestCase
{
    public function testAddPersistsWithoutFlushingUntilAsked()
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $block = new Psreassurance();
        $repository = $this->repositoryWithManager($entityManager);

        $entityManager->expects($this->once())->method('persist')->with($this->identicalTo($block));
        $entityManager->expects($this->never())->method('flush');

        $repository->add($block, false);
    }

    public function testAddFlushesWhenRequested()
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $block = new Psreassurance();
        $repository = $this->repositoryWithManager($entityManager);

        $entityManager->expects($this->once())->method('persist')->with($this->identicalTo($block));
        $entityManager->expects($this->once())->method('flush');

        $repository->add($block, true);
    }

    public function testRemoveDeletesAndOptionallyFlushes()
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $block = new Psreassurance();
        $repository = $this->repositoryWithManager($entityManager);

        $entityManager->expects($this->once())->method('remove')->with($this->identicalTo($block));
        $entityManager->expects($this->once())->method('flush');

        $repository->remove($block, true);
    }

    public function testGetAllBlockGroupsJoinedRowsByBlockAndKeepsTheFirstScalarValues()
    {
        $rows = [
            $this->row(2, 1, 'Second', 'Two', 'https://b.test', 'icon-b.svg', '', 1, 2),
            $this->row(1, 1, 'First EN', 'Alpha', '', 'icon-a.svg', '', 1, 1),
            $this->row(1, 2, 'First FR', 'Alpha FR', 'https://a.test/fr', 'other-icon.svg', 'custom.svg', 0, 1),
        ];
        $builder = new \RecordingQueryBuilder($rows);
        $repository = $this->repositoryForBuilder($builder, 'ps_');

        $result = $repository->getAllBlock();

        $this->assertSame(['*'], $builder->selects);
        $this->assertSame(['ps_psreassurance', 'pr'], $builder->from);
        $this->assertSame([
            ['pr', 'ps_psreassurance_lang', 'prl', 'pr.id_psreassurance = prl.id_psreassurance'],
        ], $builder->joins);
        $this->assertSame([['pr.position', 'ASC']], $builder->orders);
        $this->assertSame([], $builder->wheres);
        $this->assertSame([], $builder->parameters);

        $this->assertSame([2, 1], array_keys($result));
        $this->assertSame('icon-b.svg', $result[2]['icon']);
        $this->assertSame([1 => 'Second'], $result[2]['title']);
        $this->assertSame([1 => 'Two'], $result[2]['description']);
        $this->assertSame([1 => 'https://b.test'], $result[2]['url']);

        $this->assertSame('icon-a.svg', $result[1]['icon']);
        $this->assertSame('', $result[1]['custom_icon']);
        $this->assertSame(1, $result[1]['status']);
        $this->assertSame([
            1 => 'First EN',
            2 => 'First FR',
        ], $result[1]['title']);
        $this->assertSame([
            1 => 'Alpha',
            2 => 'Alpha FR',
        ], $result[1]['description']);
        $this->assertSame([
            1 => '',
            2 => 'https://a.test/fr',
        ], $result[1]['url']);
    }

    public function testGetAllBlockReturnsAnEmptyListWhenTheQueryDoes()
    {
        $builder = new \RecordingQueryBuilder([]);
        $repository = $this->repositoryForBuilder($builder, 'ps_');

        $this->assertSame([], $repository->getAllBlock());
    }

    public function testGetAllBlockOverwritesARepeatedLanguageInsteadOfAppendingIt()
    {
        $rows = [
            $this->row(5, 1, 'Old', 'Old description', '', 'icon.svg', '', 1, 1),
            $this->row(5, 1, 'New', 'New description', 'https://new.test', 'icon.svg', '', 1, 1),
        ];
        $repository = $this->repositoryForBuilder(new \RecordingQueryBuilder($rows), 'ps_');

        $result = $repository->getAllBlock();

        $this->assertSame([1 => 'New'], $result[5]['title']);
        $this->assertSame([1 => 'New description'], $result[5]['description']);
        $this->assertSame([1 => 'https://new.test'], $result[5]['url']);
    }

    public function testGetAllBlockByStatusFiltersActiveRowsForTheRequestedLanguage()
    {
        $builder = new \RecordingQueryBuilder([]);
        $repository = $this->repositoryForBuilder($builder, 'shop_');

        $this->assertSame([], $repository->getAllBlockByStatus(4));
        $this->assertSame(['shop_psreassurance', 'pr'], $builder->from);
        $this->assertSame([
            ['pr', 'shop_psreassurance_lang', 'prl', 'pr.id_psreassurance = prl.id_psreassurance'],
        ], $builder->joins);
        $this->assertSame(['pr.status = 1', 'prl.id_lang = :id_lang'], $builder->wheres);
        $this->assertSame(['id_lang' => 4], $builder->parameters);
        $this->assertSame([['pr.position', 'ASC']], $builder->orders);
    }

    public function testGetAllBlockByStatusDefaultsToLanguageOne()
    {
        $builder = new \RecordingQueryBuilder([]);
        $repository = $this->repositoryForBuilder($builder, 'ps_');

        $repository->getAllBlockByStatus();

        $this->assertSame(['id_lang' => 1], $builder->parameters);
    }

    public function testGetAllBlockByStatusPassesTheLanguageIdThroughUncast()
    {
        $builder = new \RecordingQueryBuilder([]);
        $repository = $this->repositoryForBuilder($builder, 'ps_');

        $repository->getAllBlockByStatus('3');

        $this->assertSame('3', $builder->parameters['id_lang']);
    }

    public function testGetAllBlockByStatusRewritesIconsAndFlagsSvgCustomIcons()
    {
        \blockreassurance::$static_folder_file_upload = '/upload/';
        \blockreassurance::$static_img_path_perso = '/img/perso';
        \blockreassurance::$static_img_path = '/img/';
        \ImageManager::$mimeByFile = [
            '/upload/badge.svg' => 'image/svg+xml',
            '/upload/photo.png' => 'image/png',
            '/upload/odd.svg' => 'image/svg+xml; charset=utf-8',
            '/upload/alias.svg' => 'image/svg',
        ];

        $rows = [
            $this->row(1, 1, 'Svg', 'Custom', '', 'pack/icon.svg', 'badge.svg', 1, 1),
            $this->row(2, 1, 'Png', 'Raster', '', 'pack/icon.svg', 'photo.png', 1, 2),
            $this->row(3, 1, 'Charset', 'Suffix', '', '', 'odd.svg', 1, 3),
            $this->row(4, 1, 'Alias', 'Short mime', '', '', 'alias.svg', 1, 4),
            $this->row(5, 1, 'Stock', 'No custom', '', 'reassurance/pack2/security.svg', '', 1, 5),
            $this->row(6, 1, 'Blank', 'Neither', '', '', '', 1, 6),
        ];
        $repository = $this->repositoryForBuilder(new \RecordingQueryBuilder($rows), 'ps_');

        $result = $repository->getAllBlockByStatus(1);

        $this->assertTrue($result[0]['is_svg']);
        $this->assertSame('/img/perso/badge.svg', $result[0]['custom_icon']);
        $this->assertSame('pack/icon.svg', $result[0]['icon']);

        $this->assertFalse($result[1]['is_svg']);
        $this->assertSame('/img/perso/photo.png', $result[1]['custom_icon']);

        $this->assertFalse($result[2]['is_svg']);
        $this->assertSame('/img/perso/odd.svg', $result[2]['custom_icon']);

        $this->assertTrue($result[3]['is_svg']);
        $this->assertSame('/img/perso/alias.svg', $result[3]['custom_icon']);

        $this->assertFalse($result[4]['is_svg']);
        $this->assertSame('', $result[4]['custom_icon']);
        $this->assertSame('/img/reassurance/pack2/security.svg', $result[4]['icon']);

        $this->assertFalse($result[5]['is_svg']);
        $this->assertSame('', $result[5]['icon']);
        $this->assertSame('', $result[5]['custom_icon']);
    }

    public function testZeroStringCustomIconSkipsSvgDetectionButStillRewritesThePath()
    {
        \blockreassurance::$static_folder_file_upload = '/upload/';
        \blockreassurance::$static_img_path_perso = '/img/perso';
        \blockreassurance::$static_img_path = '/img/';
        $rows = [
            $this->row(1, 1, 'Zero', 'Icon', '', 'stock.svg', '0', 1, 1),
        ];
        $repository = $this->repositoryForBuilder(new \RecordingQueryBuilder($rows), 'ps_');

        $result = $repository->getAllBlockByStatus(1);

        $this->assertFalse($result[0]['is_svg']);
        $this->assertSame('/img/perso/0', $result[0]['custom_icon']);
        $this->assertSame('stock.svg', $result[0]['icon']);
    }

    private function repositoryWithManager(EntityManagerInterface $entityManager)
    {
        $repository = new PsreassuranceRepository(null, new \RecordingConnection(), 'ps_');
        $repository->entityManager = $entityManager;

        return $repository;
    }

    private function repositoryForBuilder(\RecordingQueryBuilder $builder, $prefix)
    {
        $connection = new \RecordingConnection();
        $connection->builder = $builder;

        return new PsreassuranceRepository(null, $connection, $prefix);
    }

    private function row($id, $langId, $title, $description, $link, $icon, $customIcon, $status, $position)
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
            'position' => $position,
            'type_link' => 0,
            'id_cms' => 0,
        ];
    }
}
