<?php
namespace Czim\PxlCms\Helpers;

class Paths
{
    /**
     * External path to CMS images
     *
     * @param bool $secure
     * @return string
     */
    public static function images($secure = false)
    {
        return asset(config('pxlcms.paths.images'), $secure);
    }

    /**
     * Internal path to CMS images
     *
     * @return string
     */
    public static function imagesInternal()
    {
        return base_path(config('pxlcms.paths.base_internal') . '/' . config('pxlcms.paths.images'));
    }

    /**
     * External path to CMS uploads / files
     *
     * @param bool $secure
     * @return string
     */
    public static function uploads($secure = false)
    {
        return asset(config('pxlcms.paths.files'), $secure);
    }

    /**
     * Internal path to CMS uploads / files
     *
     * @return string
     */
    public static function uploadsInternal()
    {
        return base_path(config('pxlcms.paths.base_internal') . '/' .config('pxlcms.paths.files'));
    }
}
