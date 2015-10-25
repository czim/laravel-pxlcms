<?php
namespace Czim\PxlCms\Generator\Writer;

use Czim\DataObject\Contracts\DataObjectInterface;
use Czim\Processor\Contexts\AbstractProcessContext;
use Czim\PxlCms\Generator\Generator;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

abstract class WriterContext extends AbstractProcessContext
{
    /**
     * @var Application
     */
    protected $laravel;

    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

    /**
     * FQN name of main class being written (model/subject)
     *
     * @var string
     */
    public $fqnName;


    /**
     * @param DataObjectInterface $data
     * @param array|null          $settings
     */
    public function __construct(DataObjectInterface $data, array $settings = null)
    {
        parent::__construct($data, $settings);

        $this->files   = app(Filesystem::class);
        $this->laravel = app(Application::class);
    }


    /**
     * Writes content to file
     *
     * @param string $path
     * @param string $content
     */
    public function writeFile($path, $content)
    {
        $this->files->put($path, $content);
    }

    /**
     * Returns model stub content
     *
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function getStubContent()
    {
        return $this->files->get( $this->getStub() );
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__ . '/stubs/' . ($this->getSetting('stub') ?: $this->getDefaultStubName()) . '.stub';
    }

    /**
     * Returns default name of stub (without extension)
     *
     * @return string
     */
    abstract protected function getDefaultStubName();


    /**
     * Get the base class name from a FQN
     *
     * @param string $namespace
     * @return string
     */
    public function getClassNameFromNamespace($namespace)
    {
        $parts = explode('\\', $namespace);

        return trim($parts[ count($parts) - 1 ]);
    }

    /**
     * Get the full namespace name for a given class.
     *
     * @param  string  $name
     * @return string
     */
    public function getNamespace($name)
    {
        return trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    public function getPath($name)
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
    public function alreadyExists($rawName)
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
    public function makeDirectory($path)
    {
        if ( ! $this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }
    }

    /**
     * @param string $message
     * @param string $level
     */
    public function log($message, $level = Generator::LOG_LEVEL_DEBUG)
    {
        event('pxlcms.logmessage', [ 'message' => $message, 'level' => $level ]);
    }
}
