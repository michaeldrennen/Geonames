# geonames v7.x


[![Latest Stable Version](https://poser.pugx.org/michaeldrennen/geonames/version)](https://packagist.org/packages/michaeldrennen/geonames)  [![Total Downloads](https://poser.pugx.org/michaeldrennen/geonames/downloads)](https://packagist.org/packages/michaeldrennen/geonames)  [![License](https://poser.pugx.org/michaeldrennen/geonames/license)](https://packagist.org/packages/michaeldrennen/geonames) [![GitHub issues](https://img.shields.io/github/issues/michaeldrennen/Geonames)](https://github.com/michaeldrennen/Geonames/issues) [![GitHub forks](https://img.shields.io/github/forks/michaeldrennen/Geonames)](https://github.com/michaeldrennen/Geonames/network) [![GitHub stars](https://img.shields.io/github/stars/michaeldrennen/Geonames)](https://github.com/michaeldrennen/Geonames/stargazers) ![Travis (.org)](https://img.shields.io/travis/michaeldrennen/Geonames)  

A Laravel (php) package to interface with the geo-location services at geonames.org.

## Major Version Jump
I jumped several major versions to catch up with Larvel's major version number. Makes things a little clearer.

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

By default, `GeonamesServiceProvider` will run it for you daily at `config('geonames.update_daily_at')`. 

## Gotchas
Are you getting something like: 1071 Specified key was too long

@see https://laravel-news.com/laravel-5-4-key-too-long-error

Add this to your AppServiceProvider.php file:
```php
Schema::defaultStringLength(191);
```

## A quick word on indexes

This library contains a bunch of migrations that contain a bunch of indexes. Now not everyone will need all of the indexes.

So when you install this library, run the migrations and delete the indexes that you don't need.

Also, Laravel doesn't let you specify a key length for indexes on varchar columns. There are two indexes suffering from this limit. Instead of creating indexes on those columns the "Laravel way", I send a raw/manual query to create the indexes with the proper lengths.
