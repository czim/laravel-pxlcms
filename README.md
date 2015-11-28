# Laravel PXL CMS Adapter

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Latest Stable Version](http://img.shields.io/packagist/v/czim/laravel-pxlcms.svg)](https://packagist.org/packages/czim/laravel-pxlcms)

PXL CMS Adapter for Laravel.


## Install

Via Composer

``` bash
$ composer require czim/laravel-pxlcms
```

Add this line of code to the providers array located in your `config/app.php` file:

```php
    Czim\PxlCms\PxlCmsServiceProvider::class,
```

Publish the configuration:

``` bash
$ php artisan vendor:publish
```

## Workflow

1. Generate CMS content
2. Install and set up new Laravel project and connect it to the CMS database
3. Install this package
4. Configure this package
5. Run the generator: `artisan pxlcms:generate`


## Using Models

Generated Models work, for the most part, exactly like normal Eloquent Models.
This includes relationships, updating, eager loading and so forth. 
There are a few caveats:

- Foreign keys in CMS tables have the name of the property. They are named 'category', for instance, not 'category_id'.
  - This means that using `$model->category` on an unloaded relationship will **not** trigger the magic property since the attribute is present.
    You will get the ID integer instead.
    To get around this, simply call the relation method itself (ie. `$model->category()->first()`).  
- By default, all models are globally scoped to only include *active* records (`e_active = true`).
  - If you want to include inactive records, use the `withInactive()` scope (ie. something like `ModelName::withInactive()->get()`).
  - This depends on your pxlcms config settings, the behaviour may be changed. 
- By default, all models are ordered by their *position* (`e_position asc`).
  - If you want to prevent this, use the `unordered()` scope (ie. something like `ModelName::unordered()->first()`).
  - This depends on your pxlcms config settings, the behaviour may be changed.


## Features and Traits

- For multilingual support, **translatable** is used.
    This offers the same functionality, but uses an adapter trait that uses the CMS .._ml table content.
- By default, **rememberable** is added to every model, allowing you to cache queries with the `->remember()` method on any Eloquent Builder instance for the models.
- **Listify** is used to handle the position column on the model. You should probably let the CMS handle things, but if you need it, it is available.
- For slug handling **sluggable** is provided, through an adapter trait that uses the commonly used approach of using a `cms_slugs` table.

See the configuration file for ways to change or disable the above.


## Images and Uploads
 
 Image and File fields are set up as special relationships on the generated model.
 If you use the magic property for the relationship on the model, like so:
  
 ```php
   // For a model with a relationship: images() to the CMS Image model
   $model->images
 ```
 
... then the image results will be enriched with information about resizes and the external URLs to the images or files.

```php
   $image = $model->images->first();
   
   // This will return the external URL to the (base) image 
   $image->url
   
   // This will return the local path to the file
   $image->localPath
   
   // This will list all resizes with appended prefixes and full URLs
   $image->resizes
```

Saving images will work, but will not affect resizes.
Note that Laravel leaves you free to update the Image model's records with nonexistant files.
Additionally, no resize files will be generated for any fresh images this way.

Note that this will work for *translated* images and uploads.
Relationships will only return results for the current locale.
The locale used may be overridden (generated model code allows this by default):

```php
   // Return image results for locale other than the active application locale
   $englishImage = $model->images('en')->first();
   $dutchImage   = $model->images('nl')->first();
```


## Slugs

A modified version of the [Sluggable](https://github.com/cviebrock/eloquent-sluggable) Eloquent model trait is used to handle slugs for the models that were 'sluggified' during model generation.
This works mostly like the original Sluggify, with some exceptions:

- Slugs may be stored in the `cms_slugs` table (which can be defined in the generator config).
  If so, the change is transparent when using Sluggable methods. 
- Route model binding should work just find, look it up in the Sluggable documentation.
- The `findBy` method is now expanded with an optional `locale` parameter, wich limits slug searches to a specific locale/language: `findBy($slug, $locale = null)`.
  Likewise, the `whereSlug` scope has an optional `locale` parameter.
- Translation models should be made Sluggable for multilingual slugs.
  The translation's parent model will still implement the `SluggableInterface` and delegate the relevant calls to the translation model. 


## Running the Generator

The code generator is run through the Artisan command: `pxlcms:generate`.
It will analyze the database CMS content, if it can find it, and generate code based on the `pxclms.php` config file.

The following options are available:

```
--auto      automatic mode, skips interactivity
--dry-run   performs analysis and outputs data without writing any files
 -v         verbose mode, shows debug output
```


## To Do

- low prio: defaults based on 'options' field column
    - and perhaps auto_update for a timestamp replacement?
            use consts on the model to set the updated at timestamp attribute
            const UPDATED_AT = 'date_modified';


### Generator

- detect typical cms_m#_languages table
    - configurable whether to automatically do this or interactively
    - create relation to cms_languages if allowed, add by-locale lookups

- detect typical multilingual_labels table
    - add locale-based methods / helpers
    - add Translation helper class / methods?


## Things NOT taken into account

- Negative References (They do not get used; provide example if you find one).
- Custom Modules (They are skipped, since no reliable table information is available, if any are even used).
- Relationships with the same name as the foreign key attribute are not caught. Reasons not to touch this:
   1. It would be magic
   2. It would conflict with normal Eloquent usage
   3. It would be inefficient (would needs checks for EVERY access operation on the model)


## Credits

- [Coen Zimmerman][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/czim/laravel-pxlcms.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/czim/laravel-pxlcms.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/czim/laravel-pxlcms
[link-downloads]: https://packagist.org/packages/czim/laravel-pxlcms
[link-author]: https://github.com/czim
[link-contributors]: ../../contributors
