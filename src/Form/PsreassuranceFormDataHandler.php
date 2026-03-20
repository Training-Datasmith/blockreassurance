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
declare (strict_types=1);
namespace Presta_Shop\Module\Block_Reassurance\Form;

use Doctrine\ORM\Entity_Manager_Interface;
use Presta_Shop\Module\Block_Reassurance\Entity\Psreassurance;
use Presta_Shop\Module\Block_Reassurance\Entity\Psreassurance_Lang;
use Presta_Shop\Module\Block_Reassurance\Repository\Psreassurance_Repository;
use Presta_Shop\Presta_Shop\Core\Form\Identifiable_Object\Data_Handler\Form_Data_Handler_Interface;
use Presta_Shop_Bundle\Entity\Repository\Lang_Repository;
class Psreassurance_Form_Data_Handler implements Form_Data_Handler_Interface
{
    /**
     * @var LangRepository
     */
    private $lang_repository;
    /**
     * @var EntityManagerInterface
     */
    private $entity_manager;
    /**
     * @param PsreassuranceeRepository $psreassuranceRepository
     */
    public function __construct(Psreassurance_Repository $psreassurance_repository, Lang_Repository $lang_repository, Entity_Manager_Interface $entity_manager)
    {
        $this->lang_repository = $lang_repository;
        $this->entity_manager = $entity_manager;
    }
    /**
     * {@inheritdoc}
     */
    public function create(array $data)
    {
    }
    /**
     * {@inheritdoc}
     */
    public function update($id, array $data)
    {
    }
    /**
     * @param Psreassurance $psreassurance
     * @param array $psr_languages
     * @param int $type_link
     * @param int $id_cms
     *
     * @todo migrate this temporary function to above standard function create
     */
    public function create_langs($psreassurance, $psr_languages, $type_link, $id_cms): void
    {
        foreach ($psr_languages as $lang_id => $lang_content) {
            $lang = $this->lang_repository->find($lang_id);
            $psreassurance_lang = new Psreassurance_Lang();
            $psreassurance_lang->set_lang($lang)->set_title($lang_content->title)->set_description($lang_content->description)->set_link($lang_content->url);
            if (!empty($id_cms) && $type_link === Psreassurance::TYPE_LINK_CMS_PAGE) {
                $psreassurance->set_cms_id($id_cms);
                $link = \Context::get_context()->link;
                $psreassurance_lang->set_link($link->get_cms_link($id_cms, null, null, $lang_id));
            }
            $psreassurance->add_psreassurance_lang($psreassurance_lang);
        }
        if ($type_link == 'undefined') {
            $type_link = Psreassurance::TYPE_LINK_NONE;
        }
        $psreassurance->set_link_type($type_link);
        $this->entity_manager->persist($psreassurance);
        $this->entity_manager->flush();
    }
    /**
     * @param Psreassurance $psreassurance
     * @param array $psr_languages
     * @param int $type_link
     * @param int $id_cms
     *
     * @todo migrate this temporary function to above standard function update
     */
    public function update_langs($psreassurance, $psr_languages, $type_link, $id_cms): void
    {
        foreach ($psr_languages as $lang_id => $lang_content) {
            $lang = $this->lang_repository->find($lang_id);
            $psreassurance_lang = $psreassurance->get_psreassurance_lang_by_lang_id($lang_id);
            if (null === $psreassurance_lang) {
                continue;
            }
            $psreassurance_lang->set_title($lang_content->title)->set_description($lang_content->description)->set_link($lang_content->url);
            if (!empty($id_cms) && $type_link === Psreassurance::TYPE_LINK_CMS_PAGE) {
                $psreassurance->set_cms_id($id_cms);
                $link = \Context::get_context()->link;
                $psreassurance_lang->set_link($link->get_cms_link($id_cms, null, null, $lang_id));
            }
        }
        if ($type_link == 'undefined') {
            $type_link = Psreassurance::TYPE_LINK_NONE;
        }
        $psreassurance->set_link_type($type_link);
        $this->entity_manager->persist($psreassurance);
        $this->entity_manager->flush();
    }
}