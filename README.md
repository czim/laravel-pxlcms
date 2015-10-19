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
    To get around this, either `load()` the relationships, eager load them with `with()`, or simply call the relation method itself (ie. `$model->category->first()`).  
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


## To Do

### CmsModel

- relationships with the same name as the attribute ('category' in product f.i.)
    $product->category then returns the id, not the related model .. how to (selectively) override this?

- path helper methods?
    - cms / file upload paths, from configuration?

- images:
    - add full internal and/or external path as appended property on image
    - same for (enriched) resizes

### Generator

- allow ignoring an entire menu or group
    - note that this might result in broken references, which should be
      caught, warned about and left out

- reversed relationships:
    - if multiple reversed relationships exist to a single other model,
        this does not result in the correct / expected reversed relations,
        only a single one makes it -- need a naming scheme based on the foreign key name
        (or just add Reverse for that too.. if that happens).
        (ex. producten -> kleur1, 2 etc)

- models:   
    - store general attribute information (including type) for all attributes, not just for fillables
        for more complete ide-helper generation 

- slugs? look at the 'standardized' slug setup by Erik
    - sluggable + cms_slugs table setup
    - make it configurable
    - translatable by setting slugs on the translated models
        - maybe work something out with model -> slug -> translation.slug magic redirect

- handle dutch naming schemes, plural/singular.. (producten => productens :])
    - model names
    - relation names
    - preferrably only by exception, since it's not going to happen often!
        - add configurable 'endings' plus their plural/singular forms in config 'product(en)?' -> product, producten
    x do not mess with eloquent's table conventions!


## Things NOT taken into account

- Negative References (They do not get used; provide example if you find one).
- Custom Modules (They are skipped, since no reliable table information is available, if any are even used).


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
