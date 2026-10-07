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

namespace PrestaShop\Module\BlockReassurance\Tests;

use PHPUnit\Framework\TestCase;

abstract class ModuleTestCase extends TestCase
{
    use PrivateAccess;

    protected function setUp(): void
    {
        parent::setUp();
        \Db::reset();
        \Configuration::reset();
        \Tools::reset();
        \Language::reset();
        \Media::reset();
        \ImageManager::reset();
        \CMS::reset();
        \Context::reset();
        $_FILES = [];
        $this->swallowHeaderWarnings();
    }

    protected function tearDown(): void
    {
        $_FILES = [];
        restore_error_handler();
        parent::tearDown();
    }

    /**
     * Ajax responses call header(), which warns once PHPUnit has started output.
     */
    private function swallowHeaderWarnings()
    {
        $previous = set_error_handler(function ($severity, $message, $file, $line) use (&$previous) {
            if (false !== strpos($message, 'Cannot modify header information')
                || false !== strpos($message, 'Unable to move')) {
                return true;
            }
            if ($previous) {
                return call_user_func($previous, $severity, $message, $file, $line);
            }

            return false;
        });
    }

    protected function createModule()
    {
        return new \blockreassurance();
    }
}
