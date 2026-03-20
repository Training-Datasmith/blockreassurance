<?php

declare (strict_types=1);
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
namespace Presta_Shop\Module\Block_Reassurance\Repository;

use Doctrine\Bundle\Doctrine_Bundle\Repository\Service_Entity_Repository;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\Manager_Registry;
use Presta_Shop\Module\Block_Reassurance\Entity\Psreassurance;
/**
 * @extends ServiceEntityRepository<Psreassurance>
 *
 * @method Psreassurance|null find($id, $lockMode = null, $lockVersion = null)
 * @method Psreassurance|null findOneBy(array $criteria, array $orderBy = null)
 * @method Psreassurance[] findAll()
 * @method Psreassurance[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class Psreassurance_Repository extends Service_Entity_Repository
{
    /**
     * @var Connection the Database connection
     */
    private $connection;
    /**
     * @var string the Database prefix
     */
    private $database_prefix;
    /**
     * @param ManagerRegistry $registry
     * @param Connection $connection
     * @param string $databasePrefix
     */
    public function __construct($registry, $connection, $database_prefix)
    {
        parent::__construct($registry, Psreassurance::class);
        $this->connection = $connection;
        $this->database_prefix = $database_prefix;
    }
    public function add(Psreassurance $entity, bool $flush = false): void
    {
        $this->get_entity_manager()->persist($entity);
        if ($flush) {
            $this->get_entity_manager()->flush();
        }
    }
    public function remove(Psreassurance $entity, bool $flush = false): void
    {
        $this->get_entity_manager()->remove($entity);
        if ($flush) {
            $this->get_entity_manager()->flush();
        }
    }
    /**
     * @return array
     */
    public function get_all_block()
    {
        $result = [];
        $qb = $this->connection->create_query_builder();
        $qb->add_select('*')->from($this->database_prefix . 'psreassurance', 'pr')->left_join('pr', $this->database_prefix . 'psreassurance_lang', 'prl', 'pr.id_psreassurance = prl.id_psreassurance')->add_order_by('pr.position', 'ASC');
        $db_result = $qb->execute()->fetch_all();
        foreach ($db_result as $value) {
            if (!isset($result[$value['id_psreassurance']])) {
                $result[$value['id_psreassurance']] = $value;
                $result[$value['id_psreassurance']]['title'] = [];
                $result[$value['id_psreassurance']]['description'] = [];
                $result[$value['id_psreassurance']]['url'] = [];
            }
            $result[$value['id_psreassurance']]['title'][$value['id_lang']] = $value['title'];
            $result[$value['id_psreassurance']]['description'][$value['id_lang']] = $value['description'];
            $result[$value['id_psreassurance']]['url'][$value['id_lang']] = $value['link'];
        }
        return $result;
    }
    /**
     * @param int $id_lang
     *
     * @return array
     */
    public function get_all_block_by_status($id_lang = 1)
    {
        $qb = $this->connection->create_query_builder();
        $qb->add_select('*')->from($this->database_prefix . 'psreassurance', 'pr')->left_join('pr', $this->database_prefix . 'psreassurance_lang', 'prl', 'pr.id_psreassurance = prl.id_psreassurance')->and_where('pr.status = 1')->and_where('prl.id_lang = :id_lang')->set_parameter('id_lang', $id_lang)->add_order_by('pr.position', 'ASC');
        $result = $qb->execute()->fetch_all();
        $xml_mimes = ['image/svg', 'image/svg+xml'];
        foreach ($result as &$item) {
            $item['is_svg'] = !empty($item['custom_icon']) && in_array(\Image_Manager::get_mime_type(\blockreassurance::$static_folder_file_upload . $item['custom_icon']), $xml_mimes);
            if ($item['custom_icon'] != '') {
                $item['custom_icon'] = \blockreassurance::$static_img_path_perso . '/' . $item['custom_icon'];
            } elseif ($item['icon'] != '') {
                $item['icon'] = \blockreassurance::$static_img_path . $item['icon'];
            }
        }
        return $result;
    }
}