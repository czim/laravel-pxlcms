# Laravel PXL CMS Adapter

[![Software License][ico-license]](LICENSE.md)

PXL CMS Adapter.


## Install

Via Composer

``` bash
$ composer require czim/laravel-pxlcms
```

## Workflow

1. Generate CMS content
2. Install and set up new Laravel project
3. Require and configure this package
4. Run `artisan pxlcms:generate`

## Using Models

Generated Models work, for the most part, exactly like normal Eloquent Models.
This includes relationships, updating, eager loading and so forth. 
There are a few caveats:

- Foreign keys in CMS tables have the name of the property. They are named 'category', for instance, not 'category_id'.
  - This means that using `$model->category` on an unloaded relationship will **not** trigger the magic property since the attribute is present.
    You will get the ID integer instead.
    To get around this, simply call the relation method itself (ie. `$model->category->first()`).  
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
- For slug handling **sluggable** is provided, through an adapter trait that uses the commonly used approach of using a `cms_slugs` table. (WIP) 

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




## To Do

### Generator

- allow ignoring an entire menu or group
    - note that this might result in broken references, which should be
      caught, warned about and left out

- models:   
    x store general attribute information (including type) for all attributes, not just for fillables
        for more complete ide-helper generation
    [ not required now/yet ] 

- slugs? look at the 'standardized' slug setup by Erik
    x sluggable + cms_slugs table setup
    x make it configurable
    - translatable by setting slugs on the translated models
        - maybe work something out with model -> slug -> translation.slug magic redirect ?

- handle dutch naming schemes, plural/singular.. (producten => productens :])
    - model names
    - relation names
    - preferrably only by exception, since it's not going to happen often!
        - add configurable 'endings' plus their plural/singular forms in config 'product(en)?' -> product, producten
    x do not mess with eloquent's table conventions!

- detect typical cms_m#_languages table
    - configurable whether to automatically do this or interactively
    - create relation to cms_languages if allowed, add by-locale lookups


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
