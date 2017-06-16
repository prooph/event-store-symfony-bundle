<?php
/**
 * prooph (http://getprooph.org/)
 *
 * @see       https://github.com/prooph/event-store-symfony-bundle for the canonical source repository
 * @copyright Copyright (c) 2017 prooph software GmbH (http://prooph-software.com/)
 * @license   https://github.com/prooph/event-store-symfony-bundle/blob/master/LICENSE.md New BSD License
 */

namespace ProophTest\Bundle\EventStore\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Prooph\Bundle\EventStore\DependencyInjection\Configuration;

class ConfigurationTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    protected function getConfiguration()
    {
        return new Configuration();
    }

    public function testValuesAreInvalidIfRequiredValueIsNotProvided()
    {
        $this->assertConfigurationIsInvalid(
            array(
                array() // no values at all
            )
        );
    }


    public function testValidValues()
    {
        $this->assertConfigurationIsValid(
            array(
                array('pdo' => [
                        'driver' => 'pdo_mysql',
                        'host' => '%database_host%',
                        'port' => '%database_port%',
                        'dbname' => '%database_name%',
                        'user' => '%database_user%',
                        'password' => '%database_password%',
                ]) // no values at all
            )
        );
    }
}
