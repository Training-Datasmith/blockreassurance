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
namespace Presta_Shop\Module\Block_Reassurance\Entity;

use Doctrine\Common\Collections\Array_Collection;
use Doctrine\ORM\Mapping as ORM;
/**
 * @ORM\Table()
 *
 * @ORM\Entity(repositoryClass="PrestaShop\Module\BlockReassurance\Repository\PsreassuranceRepository")
 */
class Psreassurance
{
    public const TYPE_LINK_NONE = 0;
    public const TYPE_LINK_CMS_PAGE = 1;
    public const TYPE_LINK_URL = 2;
    /**
     * @var int
     *
     * @ORM\Id
     *
     * @ORM\Column(name="id_psreassurance", type="integer")
     *
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @var string
     *
     * @ORM\Column(name="icon", type="string", length=255)
     */
    private $icon;
    /**
     * @var string
     *
     * @ORM\Column(name="custom_icon", type="string", length=255)
     */
    private $custom_icon;
    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer")
     */
    private $status;
    /**
     * @var int
     *
     * @ORM\Column(name="position", type="integer")
     */
    private $position;
    /**
     * @var int
     *
     * @ORM\Column(name="type_link", type="integer")
     */
    private $link_type;
    /**
     * @var int
     *
     * @ORM\Column(name="id_cms", type="integer")
     */
    private $cms_id;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_add", type="datetime", nullable=false)
     */
    private $date_add;
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_upd", type="datetime", nullable=true)
     */
    private $date_upd;
    /**
     * @ORM\OneToMany(targetEntity="PrestaShop\Module\BlockReassurance\Entity\PsreassuranceLang", cascade={"persist", "remove"}, mappedBy="psreassurance")
     */
    private $psreassurance_langs;
    public function __construct()
    {
        $this->psreassurance_langs = new Array_Collection();
    }
    public function get_id(): int
    {
        return $this->id;
    }
    /**
     * @return ArrayCollection
     */
    public function get_psreassurance_langs()
    {
        return $this->psreassurance_langs;
    }
    /**
     * @return QuoteLang|null
     */
    public function get_psreassurance_lang_by_lang_id(int $lang_id)
    {
        foreach ($this->psreassurance_langs as $psreassurance_lang) {
            if ($lang_id === $psreassurance_lang->get_lang()->get_id()) {
                return $psreassurance_lang;
            }
        }
        return null;
    }
    public function add_psreassurance_lang(Psreassurance_Lang $psreassurance_lang): self
    {
        $psreassurance_lang->set_psreassurance($this);
        $this->psreassurance_langs->add($psreassurance_lang);
        return $this;
    }
    public function get_psreassurance_title(): string
    {
        if ($this->psreassurance_langs->count() <= 0) {
            return '';
        }
        $psreassurance_lang = $this->psreassurance_langs->first();
        return $psreassurance_lang->get_title();
    }
    public function get_psreassurance_description(): string
    {
        if ($this->psreassurance_langs->count() <= 0) {
            return '';
        }
        $psreassurance_lang = $this->psreassurance_langs->first();
        return $psreassurance_lang->get_description();
    }
    public function get_icon(): string
    {
        return $this->icon;
    }
    public function set_icon(string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }
    public function get_custom_icon(): string
    {
        return $this->custom_icon;
    }
    public function set_custom_icon(string $custom_icon): self
    {
        $this->custom_icon = $custom_icon;
        return $this;
    }
    public function get_status(): int
    {
        return $this->status;
    }
    public function set_status(int $status): self
    {
        $this->status = $status;
        return $this;
    }
    public function get_position(): int
    {
        return $this->position;
    }
    public function set_position(int $position): self
    {
        $this->position = $position;
        return $this;
    }
    public function get_link_type(): int
    {
        return $this->link_type;
    }
    public function set_link_type(int $link_type): self
    {
        $this->link_type = $link_type;
        return $this;
    }
    public function get_cms_id(): int
    {
        return $this->cms_id;
    }
    public function set_cms_id(int $cms_id): self
    {
        $this->cms_id = $cms_id;
        return $this;
    }
    /**
     * Set dateAdd.
     */
    public function set_date_add(\DateTime $date_add): self
    {
        $this->date_add = $date_add;
        return $this;
    }
    /**
     * Get dateAdd.
     */
    public function get_date_add(): \DateTime
    {
        return $this->date_add;
    }
    /**
     * Set dateUpd.
     */
    public function set_date_upd(\DateTime $date_upd): self
    {
        $this->date_upd = $date_upd;
        return $this;
    }
    /**
     * Get dateUpd.
     */
    public function get_date_upd(): \DateTime
    {
        return $this->date_upd;
    }
}