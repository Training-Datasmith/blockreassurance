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

/*
 * Minimal PrestaShop stand-ins so the module can be exercised without a shop.
 * Behaviour that matters to the module (return values, recorded calls) is explicit.
 */

namespace PrestaShop\PrestaShop\Core\Module {
    interface WidgetInterface
    {
        public function renderWidget($hookName, array $configuration);

        public function getWidgetVariables($hookName, array $configuration);
    }
}

namespace PrestaShopBundle\Entity {
    class Lang
    {
        private $id;

        public function __construct($id = 0)
        {
            $this->id = $id;
        }

        public function getId()
        {
            return $this->id;
        }
    }
}

namespace PrestaShopBundle\Entity\Repository {
    class LangRepository
    {
        public function find($id)
        {
            return null;
        }
    }
}

namespace Doctrine\ORM {
    interface EntityManagerInterface
    {
        public function persist($object);

        public function flush();

        public function remove($object);
    }
}

namespace PrestaShop\PrestaShop\Core\Form\IdentifiableObject\DataHandler {
    interface FormDataHandlerInterface
    {
        public function create(array $data);

        public function update($id, array $data);
    }
}

namespace Doctrine\Bundle\DoctrineBundle\Repository {
    class ServiceEntityRepository
    {
        public $entityManager;

        public function __construct($registry = null, $entityClass = null)
        {
        }

        public function getEntityManager()
        {
            return $this->entityManager;
        }
    }
}

namespace {
    if (!defined('_PS_VERSION_')) {
        define('_PS_VERSION_', '8.1.7');
    }
    if (!defined('_PS_ROOT_DIR_')) {
        define('_PS_ROOT_DIR_', sys_get_temp_dir() . '/blockreassurance-ps-root');
    }
    if (!is_dir(_PS_ROOT_DIR_)) {
        mkdir(_PS_ROOT_DIR_, 0777, true);
    }
    if (!defined('__PS_BASE_URI__')) {
        define('__PS_BASE_URI__', '/');
    }
    if (!defined('_PS_MODULE_DIR_')) {
        define('_PS_MODULE_DIR_', _PS_ROOT_DIR_ . '/modules/');
    }
    if (!defined('_DB_PREFIX_')) {
        define('_DB_PREFIX_', 'ps_');
    }
    if (!defined('_MYSQL_ENGINE_')) {
        define('_MYSQL_ENGINE_', 'InnoDB');
    }

    class Db
    {
        private static $instance;

        public $rows = [];

        public $executeSQueue;

        public $selects = [];

        public $executed = [];

        public $updates = [];

        public $deletes = [];

        public $getRowResult;

        public $getValueResult;

        public $updateResult = true;

        public $updateResults;

        public $deleteResult = true;

        public $executeResults;

        public $msgError = 'sql error';

        public static function getInstance()
        {
            if (!self::$instance) {
                self::$instance = new self();
            }

            return self::$instance;
        }

        public static function reset()
        {
            self::$instance = new self();
        }

        public function executeS($sql)
        {
            $this->selects[] = $sql;
            if (is_array($this->executeSQueue)) {
                $rows = array_shift($this->executeSQueue);

                return null === $rows ? [] : $rows;
            }

            return $this->rows;
        }

        public function execute($sql)
        {
            $this->executed[] = $sql;
            if (is_array($this->executeResults) && count($this->executeResults) > 0) {
                return array_shift($this->executeResults);
            }

            return true;
        }

        public function getMsgError()
        {
            return $this->msgError;
        }

        public function update($table, $data, $where, $limit = 0, $nullValues = false, $useCache = true, $addPrefix = true)
        {
            $this->updates[] = [
                'table' => $table,
                'data' => $data,
                'where' => $where,
            ];
            if (is_array($this->updateResults) && count($this->updateResults) > 0) {
                return array_shift($this->updateResults);
            }

            return $this->updateResult;
        }

        public function delete($table, $where, $limit = 0, $useCache = true, $addPrefix = true)
        {
            $this->deletes[] = [
                'table' => $table,
                'where' => $where,
            ];

            return $this->deleteResult;
        }

        public function getRow($sql, $useCache = true)
        {
            $this->selects[] = $sql;

            return $this->getRowResult;
        }

        public function getValue($sql, $useCache = true)
        {
            $this->selects[] = $sql;

            return $this->getValueResult;
        }
    }

    class Configuration
    {
        public static $values = [];

        public static $updates = [];

        public static $deleted = [];

        public static $failKeys = [];

        public static function get($key, $idLang = null, $idShopGroup = null, $idShop = null, $default = false)
        {
            if (array_key_exists($key, self::$values)) {
                return self::$values[$key];
            }

            return $default;
        }

        public static function updateValue($key, $values, $html = false, $idShopGroup = null, $idShop = null)
        {
            self::$updates[] = [$key, $values];
            if (in_array($key, self::$failKeys, true)) {
                return false;
            }
            self::$values[$key] = $values;

            return true;
        }

        public static function deleteByName($key)
        {
            self::$deleted[] = $key;
            unset(self::$values[$key]);

            return true;
        }

        public static function reset()
        {
            self::$values = [];
            self::$updates = [];
            self::$deleted = [];
            self::$failKeys = [];
        }
    }

    class Tools
    {
        public static $values = [];

        public static function getValue($key, $defaultValue = false)
        {
            if (array_key_exists($key, self::$values)) {
                return self::$values[$key];
            }

            return $defaultValue;
        }

        public static function getCurrentUrlProtocolPrefix()
        {
            return 'http://';
        }

        public static function reset()
        {
            self::$values = [];
        }
    }

    class Language
    {
        public static $languages = [];

        public static $calls = [];

        public static function getLanguages($active = false)
        {
            self::$calls[] = $active;

            return self::$languages;
        }

        public static function reset()
        {
            self::$calls = [];
            self::$languages = [
                ['id_lang' => 1, 'locale' => 'en-US'],
            ];
        }
    }

    class Link
    {
        public $cmsLinks = [];

        public $adminLinks = [];

        public function __construct($protocolLink = null, $protocolContent = null)
        {
        }

        public function getBaseLink($idShop = null, $ssl = null, $relativeProtocol = false)
        {
            return 'http://localhost/';
        }

        public function getAdminLink($controller, $withToken = true, $params = [], $locale = null)
        {
            $this->adminLinks[] = $controller;

            return 'admin.php?controller=' . $controller;
        }

        public function getCMSLink($cms, $alias = null, $ssl = null, $idLang = null)
        {
            $this->cmsLinks[] = [
                'cms' => $cms,
                'alias' => $alias,
                'ssl' => $ssl,
                'id_lang' => $idLang,
            ];

            return 'cms/' . $cms . '/lang/' . $idLang;
        }
    }

    class Smarty
    {
        public $assigned = [];

        public function assign($tplVar, $value = null, $nocache = false)
        {
            if (is_array($tplVar)) {
                foreach ($tplVar as $key => $item) {
                    $this->assigned[$key] = $item;
                }

                return;
            }

            $this->assigned[$tplVar] = $value;
        }
    }

    class Translator
    {
        public function trans($id, array $parameters = [], $domain = null, $locale = null)
        {
            return $id;
        }
    }

    class Context
    {
        /** @var Context|null */
        public static $instance;

        public $link;

        public $language;

        public $controller;

        public $smarty;

        public $employee;

        public static function getContext()
        {
            if (!self::$instance) {
                self::reset();
            }

            return self::$instance;
        }

        public static function reset()
        {
            $context = new self();
            $context->link = new Link();
            $context->smarty = new Smarty();
            $context->language = new \stdClass();
            $context->language->id = 1;
            $context->language->locale = 'en-US';
            $context->employee = new \stdClass();
            $context->employee->id_lang = 1;
            $context->controller = new ContextController();
            self::$instance = $context;
        }

        public function getTranslator()
        {
            return new Translator();
        }
    }

    class ContextController
    {
        public $css = [];

        public $js = [];

        public $stylesheets = [];

        public $javascripts = [];

        public $container;

        public function addCSS($path, $media = 'all', $priority = 50, $inline = false)
        {
            $this->css[] = [$path, $media];
        }

        public function addJS($path, $checkPath = true)
        {
            $this->js[] = $path;
        }

        public function registerStylesheet($id, $path, $params = [])
        {
            $this->stylesheets[$id] = $path;
        }

        public function registerJavascript($id, $path, $params = [])
        {
            $this->javascripts[$id] = $path;
        }

        public function getContainer()
        {
            return $this->container;
        }
    }

    class Media
    {
        public static $jsDefs = [];

        public static function addJsDef($def)
        {
            self::$jsDefs[] = $def;
        }

        public static function reset()
        {
            self::$jsDefs = [];
        }
    }

    class CMS
    {
        public static $pages = [];

        public static function listCms($idLang = null, $idShop = false)
        {
            return self::$pages;
        }

        public static function reset()
        {
            self::$pages = [];
        }
    }

    class ImageManager
    {
        public static $mimeByFile = [];

        public static $validateUploadResult = false;

        public static function getMimeType($filename)
        {
            if (array_key_exists($filename, self::$mimeByFile)) {
                return self::$mimeByFile[$filename];
            }

            return 'application/octet-stream';
        }

        public static function validateUpload($file, $maxFileSize = 0, $types = null, $mimeTypes = null)
        {
            return self::$validateUploadResult;
        }

        public static function reset()
        {
            self::$mimeByFile = [];
            self::$validateUploadResult = false;
        }
    }

    class Tab
    {
        public static $created = [];

        public $active;

        public $class_name;

        public $name = [];

        public $id_parent;

        public $module;

        public $added = false;

        public function __construct()
        {
            self::$created[] = $this;
        }

        public function add($autoDate = true, $nullValues = false)
        {
            $this->added = true;

            return true;
        }

        public static function reset()
        {
            self::$created = [];
        }
    }

    class ObjectModel
    {
        const TYPE_INT = 1;
        const TYPE_BOOL = 2;
        const TYPE_STRING = 3;
        const TYPE_FLOAT = 4;
        const TYPE_DATE = 5;
        const TYPE_HTML = 6;
        const TYPE_NOTHING = 7;
        const TYPE_SQL = 8;

        public function __construct($id = null, $idLang = null, $idShop = null)
        {
        }
    }

    class Module
    {
        public $name;

        public $tab;

        public $version;

        public $author;

        public $need_instance;

        public $bootstrap;

        public $context;

        public $_path;

        public $_errors = [];

        public $ps_versions_compliancy;

        public $smarty;

        public $local_path;

        public $services = [];

        public $displayed = [];

        public $fetched = [];

        public $cached = false;

        public $registeredHooks = [];

        public $hooksAlreadyRegistered = [];

        public $registerHookResult = true;

        public $parentInstallResult = true;

        public $parentUninstallResult = true;

        public function __construct()
        {
            $this->context = Context::getContext();
            if (!$this->context->smarty) {
                $this->context->smarty = new Smarty();
            }
            $this->smarty = $this->context->smarty;
        }

        public function trans($id, array $parameters = [], $domain = null, $locale = null)
        {
            return $id;
        }

        public function install()
        {
            return $this->parentInstallResult;
        }

        public function uninstall()
        {
            return $this->parentUninstallResult;
        }

        public function registerHook($hookName, $shopList = null)
        {
            $this->registeredHooks[] = $hookName;

            return $this->registerHookResult;
        }

        public function isRegisteredInHook($hookName)
        {
            return in_array($hookName, $this->hooksAlreadyRegistered, true);
        }

        public function get($serviceName, $force = false)
        {
            if (array_key_exists($serviceName, $this->services)) {
                return $this->services[$serviceName];
            }

            return null;
        }

        public function display($file, $template, $cacheId = null, $compileId = null)
        {
            $this->displayed[] = $template;

            return $template;
        }

        public function isCached($template, $cacheId = null)
        {
            return $this->cached;
        }

        public function fetch($template, $cacheId = null, $compileId = null)
        {
            $this->fetched[] = [$template, $cacheId];

            return 'fetched:' . $template;
        }

        public function getCacheId($name = null)
        {
            return 'cache-' . $name;
        }
    }

    class ModuleAdminController
    {
        public $module;

        public $context;

        public $ajaxOutput;

        public function __construct()
        {
            $this->context = Context::getContext();
        }

        protected function ajaxRender($value = null, $controller = null, $method = null)
        {
            $this->ajaxOutput = $value;
        }
    }

    class InMemoryBlockRepository
    {
        public $allBlocks = [];

        public $activeBlocks = [];

        public $statusLanguageIds = [];

        public function getAllBlock()
        {
            return $this->allBlocks;
        }

        public function getAllBlockByStatus($idLang = 1)
        {
            $this->statusLanguageIds[] = $idLang;

            return $this->activeBlocks;
        }
    }

    class FakeServiceContainer
    {
        public $services = [];

        public function get($id)
        {
            if (!array_key_exists($id, $this->services)) {
                throw new RuntimeException('Missing service ' . $id);
            }

            return $this->services[$id];
        }
    }

    class FakeFormDataHandler
    {
        public $created = [];

        public $updated = [];

        public function createLangs($psreassurance, $psrLanguages, $typeLink, $idCms)
        {
            $this->created[] = [$psreassurance, $psrLanguages, $typeLink, $idCms];
        }

        public function updateLangs($psreassurance, $psrLanguages, $typeLink, $idCms)
        {
            $this->updated[] = [$psreassurance, $psrLanguages, $typeLink, $idCms];
        }
    }

    class FakeBlockFinder
    {
        public $found;

        public function find($id)
        {
            return $this->found;
        }
    }

    class RecordingQueryBuilder
    {
        public $rows;

        public $selects = [];

        public $from;

        public $joins = [];

        public $orders = [];

        public $wheres = [];

        public $parameters = [];

        public function __construct(array $rows)
        {
            $this->rows = $rows;
        }

        public function addSelect($select)
        {
            $this->selects[] = $select;

            return $this;
        }

        public function from($table, $alias)
        {
            $this->from = [$table, $alias];

            return $this;
        }

        public function leftJoin($fromAlias, $join, $alias, $condition = null)
        {
            $this->joins[] = [$fromAlias, $join, $alias, $condition];

            return $this;
        }

        public function addOrderBy($sort, $order = null)
        {
            $this->orders[] = [$sort, $order];

            return $this;
        }

        public function andWhere($where)
        {
            $this->wheres[] = $where;

            return $this;
        }

        public function setParameter($key, $value, $type = null)
        {
            $this->parameters[$key] = $value;

            return $this;
        }

        public function execute()
        {
            return new RecordingStatement($this->rows);
        }
    }

    class RecordingStatement
    {
        private $rows;

        public function __construct(array $rows)
        {
            $this->rows = $rows;
        }

        public function fetchAll($fetchMode = null)
        {
            return $this->rows;
        }
    }

    class RecordingConnection
    {
        public $builder;

        public function createQueryBuilder()
        {
            return $this->builder;
        }
    }
}
