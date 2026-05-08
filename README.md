# geonames v13.x


[![Latest Stable Version](https://poser.pugx.org/michaeldrennen/geonames/version)](https://packagist.org/packages/michaeldrennen/geonames)  [![Total Downloads](https://poser.pugx.org/michaeldrennen/geonames/downloads)](https://packagist.org/packages/michaeldrennen/geonames)  [![License](https://poser.pugx.org/michaeldrennen/geonames/license)](https://packagist.org/packages/michaeldrennen/geonames) [![GitHub issues](https://img.shields.io/github/issues/michaeldrennen/Geonames)](https://github.com/michaeldrennen/Geonames/issues) [![GitHub forks](https://img.shields.io/github/forks/michaeldrennen/Geonames)](https://github.com/michaeldrennen/Geonames/network) [![GitHub stars](https://img.shields.io/github/stars/michaeldrennen/Geonames)](https://github.com/michaeldrennen/Geonames/stargazers)

A Laravel (php) package to interface with the geo-location services at geonames.org.

## Major Version Jump
I jumped several major versions to catch up with Larvel's major version number. Makes things a little clearer.

## Notes
There is still a lot that needs to be done to make this package "complete". I've gotten it to a point where I can use it for my next project. As time allows, I will improve the documentation and testing that comes with this package. Thanks for understanding.

## Installation
```
composer require michaeldrennen/geonames
```

Since Laravel 5.5, the package will automatically register itself via package discovery. 

If you're using an older version or have discovery disabled, add the `geonames` provider to the `providers` array in your `config/app.php` config file:

```php
MichaelDrennen\Geonames\GeonamesServiceProvider::class,
```

After that, Run migrate command:

```
php artisan migrate
```

To publish package files, run:

```
php artisan vendor:publish --provider="MichaelDrennen\Geonames\GeonamesServiceProvider"
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

**To download `cities1000.zip` along with all country data:**
```bash
php artisan geonames:install --country="*" --country="cities1000.zip"
```

**To download only `cities1000.zip`:**
```bash
php artisan geonames:install --country="cities1000.zip"
```
## Adding Countries Incrementally

If you have already installed a set of countries (e.g., US and CA) and wish to add more countries (e.g., BR) without reinstalling the entire dataset, you can use the `geonames:add` command:

```bash
php artisan geonames:add --country=BR
```

This command will:
- Download only the necessary data files for the specified new countries.
- Append new geonames records and alternate names to your existing tables.
- Update your `GeoSetting` to reflect the newly added countries.
- Reload smaller, non-country-specific tables like `admin-1-code`, `admin-2-code`, `feature-code`, and `feature-class` to ensure consistency.

You can specify multiple countries:
```bash
php artisan geonames:add --country=BR --country=AR
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

By default, `GeonamesServiceProvider` will run it for you daily at `config('geonames.update_daily_at')`. You can change it in your `.env` file using `GEONAMES_UPDATE_DAILY_AT` key or in `config/geonames.php` file (if you have published it).

## Country Info Table

This package now includes functionality to download and import detailed country information from geonames.org's `countryInfo.txt` file. This data is stored in the `countryinfo` table.

To install this data, you can either:

1.  **Run it as part of the main installation:** The `geonames:install` command now automatically includes the country info data.
    ```bash
    php artisan geonames:install
    ```
    or with specific options:
    ```bash
    php artisan geonames:install --country=US --language=en
    ```

2.  **Run the command directly:** If you need to install only the country information or re-import it, you can use the dedicated command:
    ```bash
    php artisan geonames:countryinfo
    ```
    If you need to overwrite existing country info data, use the `--force` option:
    ```bash
    php artisan geonames:countryinfo --force
    ```

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
