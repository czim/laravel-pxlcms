<?php
namespace Czim\PxlCms\Generator\Writer;

use Czim\DataObject\Contracts\DataObjectInterface;
use Czim\Processor\Contexts\AbstractProcessContext;
use Czim\PxlCms\Generator\Generator;
use Czim\PxlCms\Models\CmsModel;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class WriterContext extends AbstractProcessContext
{
    const DEFAULT_STUB_FILE = 'model';


    /**
     * @var Application
     */
    protected $laravel;

    /**
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;


    /**
     * FQN name of model
     *
     * @var string
     */
    public $fqnName;

    /**
     * List of imports that aren't required for the model
     *
     * @var array
     * @todo phase out
     */
    public $importsNotUsed = [];

    /**
     * Which of the standard special relationships models were used
     * in this model's context
     *
     * @var array
     */
    public $standardModelsUsed = [];

    /**
     * Whether the rememberable trait is blocked for the model
     *
     * @var bool
     */
    public $blockRememberableTrait = false;

    /**
     * The 'use' imports to include
     *
     * @var array
     * @todo phase in
     */
    public $imports = [];

    /**
     * Whether the model needs the full sluggable treatment (not true if separate translated model does!)
     *
     * @var bool
     */
    public $modelIsSluggable = false;

    /**
     * Whether the model is the parent of a translation model that is sluggable
     *
     * @var bool
     */
    public $modelIsParentOfSluggableTranslation = false;


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
        return __DIR__ . '/stubs/' . ($this->getSetting('stub') ?: self::DEFAULT_STUB_FILE) . '.stub';
    }


    // ------------------------------------------------------------------------------
    //      Model-specific
    // ------------------------------------------------------------------------------

    /**
     * Build Fully Qualified Namespace for a model name
     *
     * @param string $name
     * @return string
     */
    public function makeFqnForModelName($name)
    {
        return config('pxlcms.generator.namespace.models') . "\\" . studly_case($name);
    }


    /**
     * Get the model name from a FQN
     *
     * @param string $namespace
     * @return string
     */
    public function getModelNameFromNamespace($namespace)
    {
        $parts = explode('\\', $namespace);

        return trim($parts[ count($parts) - 1 ]);
    }

    /**
     * Returns the model name (FQN if not to be imported) for a standard model
     * based on CmsModel const values for RELATION_TYPEs
     *
     * @param int $type
     * @return string
     */
    public function getModelNamespaceForSpecialModel($type)
    {
        $typeName = $this->getConfigNameForStandardModelType($type);

        if (    ! is_null($typeName)
            &&  config('pxlcms.generator.models.include_namespace_of_standard_models')
        ) {
            return $this->getModelNameFromNamespace(config('pxlcms.generator.standard_models.' . $typeName));
        }

        return '\\' . config('pxlcms.generator.standard_models.' . $typeName);
    }

    /**
     * Returns the special model type name used for config properties
     * based on CmsModel const values for RELATION_TYPEs
     *
     * @param int $type
     * @return null|string
     */
    protected function getConfigNameForStandardModelType($type)
    {
        switch ($type) {

            case CmsModel::RELATION_TYPE_IMAGE:
                return 'image';

            case CmsModel::RELATION_TYPE_FILE:
                return 'file';

            case CmsModel::RELATION_TYPE_CATEGORY:
                return 'category';

            case CmsModel::RELATION_TYPE_CHECKBOX:
                return 'checkbox';

            // default omitted on purpose
        }

        return null;
    }


    // ------------------------------------------------------------------------------
    //      General
    // ------------------------------------------------------------------------------

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
