# geonames v1.x


[![Latest Stable Version](https://poser.pugx.org/michaeldrennen/geonames/version)](https://packagist.org/packages/michaeldrennen/geonames)  [![Total Downloads](https://poser.pugx.org/michaeldrennen/geonames/downloads)](https://packagist.org/packages/michaeldrennen/geonames)  [![License](https://poser.pugx.org/michaeldrennen/geonames/license)](https://packagist.org/packages/michaeldrennen/geonames)

A Laravel (php) package to interface with the geo-location services at geonames.org.

## Notes
There is still a lot that needs to be done to make this package "complete". I've gotten it to a point where I can use it for my next project. As time allows, I will improve the documentation and testing that comes with this package. Thanks for understanding.

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
```php
php artisan geonames:install --country=US --country=CA --country=MX --language=en
```

Want to just install everything in the geonames database?
```php
php artisan geonames:install
```

## Maintenance
Now that you have the geonames database up and running on your system, you need to keep it up-to-date.

I have an update script that you need to schedule in Laravel to run every day.

Some info on how to schedule Laravel artisan commands:

https://laravel.com/docs/5.6/scheduling#scheduling-artisan-commands

You can read this notice at: http://download.geonames.org/export/dump/

<code>The "last modified" timestamp is in Central European Time. </code>

It looks like geonames updates their data around 3AM CET.

So if you schedule your system to run the geonames:update artisan command after 4AM CET, you should be good to go.

I like to keep my servers running on GMT. Keeps things consistent.

(Central European Time is 1 hour ahead of Greenwich Mean Time)

Assuming your servers are running on GMT, your update command would look like: 
```php
$schedule->command('geonames:update')->dailyAt('3:00');
```

The update artisan command will handle the updates and deletes to the geonames table.