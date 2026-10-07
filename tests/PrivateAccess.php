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

trait PrivateAccess
{
    protected function callPrivate($object, $method, array $arguments = [])
    {
        $reflection = new \ReflectionMethod(get_class($object), $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $arguments);
    }

    protected function setPrivateProperty($object, $property, $value)
    {
        $reflection = new \ReflectionClass($object);
        while (!$reflection->hasProperty($property) && $reflection->getParentClass()) {
            $reflection = $reflection->getParentClass();
        }
        $reflectionProperty = $reflection->getProperty($property);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($object, $value);
    }

    protected function getPrivateProperty($object, $property)
    {
        $reflection = new \ReflectionClass($object);
        while (!$reflection->hasProperty($property) && $reflection->getParentClass()) {
            $reflection = $reflection->getParentClass();
        }
        $reflectionProperty = $reflection->getProperty($property);
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty->getValue($object);
    }
}
