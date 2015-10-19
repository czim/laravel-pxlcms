<?php
namespace Czim\PxlCms\Helpers;

class Paths
{
    /**
     * External path to CMS images
     *
     * @param string $file      file to add to path
     * @param bool   $secure
     * @return string
     */
    public static function images($file = null, $secure = false)
    {
        return asset(self::appendToPath(config('pxlcms.paths.images'), $file), $secure);
    }

    /**
     * Internal path to CMS images
     *
     * @param string $file      file to add to path
     * @return string
     */
    public static function imagesInternal($file = null)
    {
        return base_path(
            self::appendToPath(
                self::appendToPath(config('pxlcms.paths.base_internal'), config('pxlcms.paths.images')),
                $file
            )
        );
    }

    /**
     * External path to CMS uploads / files
     *
     * @param string $file      file to add to path
     * @param bool $secure
     * @return string
     */
    public static function uploads($file = null, $secure = false)
    {
        return asset(self::appendToPath(config('pxlcms.paths.files'), $file), $secure);
    }

    /**
     * Internal path to CMS uploads / files
     *
     * @param string $file      file to add to path
     * @return string
     */
    public static function uploadsInternal($file = null)
    {
        return base_path(
            self::appendToPath(
                self::appendToPath(config('pxlcms.paths.base_internal'), config('pxlcms.paths.files')),
                $file
            )
        );
    }

    /**
     * Append a subpath or file to a path
     *
     * @param string $path
     * @param string $file
     * @return string
     */
    protected static function appendToPath($path, $file)
    {
        if (empty($file)) return $path;

        return rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $file;
    }
}
