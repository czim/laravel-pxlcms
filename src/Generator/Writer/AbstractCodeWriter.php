<?php
namespace Czim\PxlCms\Generator\Writer;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

abstract class AbstractCodeWriter
{
    /**
     * The Laravel application instance.
     *
     * @var Application
     */
    protected $laravel;

    /**
     * The filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;


    /**
     * Create a new controller creator command instance.
     *
     * @param Filesystem  $files
     * @param Application $laravel
     */
    public function __construct(Filesystem $files, Application $laravel)
    {
        $this->files   = $files;
        $this->laravel = $laravel;
    }


    /**
     * Build the class with the data set
     *
     * @return string
     */
    protected function buildClass()
    {
        $stub = $this->files->get($this->getStub());

        return $this->doReplaces($stub);
    }

    /**
     * Performs all replaces in the stub content
     *
     * @param  string  $stub
     * @return string
     */
    abstract protected function doReplaces($stub);


    /**
     * Get the full namespace name for a given class.
     *
     * @param  string  $name
     * @return string
     */
    protected function getNamespace($name)
    {
        return trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {
        $name = str_replace($this->laravel->getNamespace(), '', $name);

        return $this->laravel['path'] . '/' . str_replace('\\', '/', $name) . '.php';
    }

    /**
     * Determine if the class already exists.
     *
     * @param  string  $rawName
     * @return bool
     */
    protected function alreadyExists($rawName)
    {
        $name = $this->parseName($rawName);

        return $this->files->exists($path = $this->getPath($name));
    }

    /**
     * Parse the name and format according to the root namespace.
     *
     * @param  string  $name
     * @return string
     */
    protected function parseName($name)
    {
        $rootNamespace = $this->laravel->getNamespace();

        if (Str::startsWith($name, $rootNamespace)) {
            return $name;
        }

        if (Str::contains($name, '/')) {
            $name = str_replace('/', '\\', $name);
        }

        return $this->parseName($this->getDefaultNamespace(trim($rootNamespace, '\\')) . '\\' . $name);
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace;
    }

    /**
     * Build the directory for the class if necessary.
     *
     * @param  string  $path
     * @return string
     */
    protected function makeDirectory($path)
    {
        if ( ! $this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }
    }

    /**
     * Returns a number of 'tabs' in the form of 4 spaces each
     *
     * @param int $count
     * @return string
     */
    protected function tab($count = 1)
    {
        return str_repeat(' ', 4 * $count);
    }

    /**
     * Returns length of longest key in key-value pair array
     *
     * @param array $array
     * @return int
     */
    protected function getLongestKey(array $array)
    {
        $longest = 0;

        foreach ($array as $key => $value) {
            if ($longest > strlen($key)) continue;

            $longest = strlen($key);
        }

        return $longest;
    }
}
