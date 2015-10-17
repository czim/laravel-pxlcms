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


## To Do

### CmsModel

- e_active global scope by default
- listify + e_position by default

- relationships with the same name as the attribute ('category' in product f.i.)
    $product->category then returns the id, not the related model .. how to (selectively) override this?

- categories ?

- path helper methods?
    - cms / file upload paths, from configuration?

- images:
    - add full internal and/or external path as appended property on image
    - same for (enriched) resizes

### Generator

- relationship handling:
    - negative reference
    - autosort relation?

- models:   
    - store general attribute information (including type) for all attributes, not just for fillables
        for more complete ide-helper generation 

- slugs? look at the 'standardized' slug setup by Erik

- handle dutch naming schemes, plural/singular.. (producten => productens :])
    - model names
    - relation names
    x do not mess with eloquent's table conventions!

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
