# geonames

DO NOT USE IN PRODUCTION YET.

[![Latest Stable Version](https://poser.pugx.org/michaeldrennen/geonames/version)](https://packagist.org/packages/michaeldrennen/geonames)  [![Total Downloads](https://poser.pugx.org/michaeldrennen/geonames/downloads)](https://packagist.org/packages/michaeldrennen/geonames)  [![License](https://poser.pugx.org/michaeldrennen/geonames/license)](https://packagist.org/packages/michaeldrennen/geonames)

A Laravel (php) package to interface with the geo-location services at geonames.org.

## Installation
```
composer require michaeldrennen/geonames
```
And then add `geonames` provider to `providers` array in `app.php` config file:

```php
    MichaelDrennen\Geonames\GeonamesServiceProvider::class,
```

After that, Run migrate command:

```
php artisan migrate
```

Want to install all of the geonames records for the US, Canada, and Mexico as well as pull in the feature codes 
definitions file in English? 

<code>php artisan geonames:install --country=US --country=CA --country=MX --language=en</code>
