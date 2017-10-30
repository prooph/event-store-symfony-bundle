# Getting started

This documentation covers just the configuration of the Prooph Event Store in Symfony.
To inform yourself about the Event Store please have a look at the
[official documentation](http://docs.getprooph.org/event-store/).

## Download the Bundle

Download the bundle using composer by running
```bash
composer require prooph/event-store-symfony-bundle
```
at the root of your Symfony project.

## Enable the Bundle

To start using this bundle, register the bundle in your application's kernel class:
```php
<?php
// app/AppKernel.php
// …
class AppKernel extends Kernel
{
    // …
    public function registerBundles()
    {
        $bundles = [
            // …
            new Prooph\Bundle\EventStore\ProophEventStoreBundle(),
            // …
        ];
        // …
    }
    // …
}
```

or, if you are using [the new flex structure](https://symfony.com/doc/current/setup/flex.html):
```php
<?php
// config/bundles.php

return [
    // …
    Prooph\Bundle\EventStore\ProophEventStoreBundle::class => ['all' => true],
];
```
