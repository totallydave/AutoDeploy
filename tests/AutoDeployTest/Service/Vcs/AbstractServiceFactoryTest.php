<?php
/**
 * Created by PhpStorm.
 * User: kingd
 * Date: 18/01/16
 * Time: 17:20
 */

namespace AutoDeployTest\Service;


class AbstractServiceFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test creation of new service with an existing scheme-classd
     *
     * @param string $uri           THe URI to create
     * @param string $expectedClass The class expected
     *
     * @dataProvider createUriWithFactoryProvider
     */
    public function testCreateServiceWithFactory($config, $expectedClass)
    {
        $class =
    }

    public function createUriWithFactoryProvider()
    {
        return array(
            array('http://example.com', 'Zend\Uri\Http'),
            array('https://example.com', 'Zend\Uri\Http'),
            array('mailto://example.com', 'Zend\Uri\Mailto'),
            array('file://example.com', 'Zend\Uri\File'),
        );
    }
}