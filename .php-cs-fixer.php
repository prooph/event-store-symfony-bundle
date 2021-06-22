<?php

$config = new Prooph\CS\Config\Prooph();
$config->getFinder()->in(__DIR__)->exclude('vendor')->exclude('test/Command/Fixture/var');

return $config;
