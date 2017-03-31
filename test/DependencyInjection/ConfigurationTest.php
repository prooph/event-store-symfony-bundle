<?php
/**
 * Created by PhpStorm.
 * User: mablae
 * Date: 31.03.2017
 * Time: 16:00
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
