<?php
namespace Czim\PxlCms\Generator\Writer;

use Czim\PxlCms\Generator\Exceptions\ModelFileAlreadyExistsException;
use Czim\PxlCms\Generator\Generator;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CmsModelWriter
{
    const IMPORT_TRAIT_TRANSLATABLE = 'translatable';
    const IMPORT_TRAIT_LISTIFY      = 'listify';

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
     * Analyzer output data for generating the model
     *
     * @var array
     */
    protected $data = [];

    /**
     * FQN name of model
     *
     * @var string
     */
    protected $fqnName;

    /**
     * List of imports that aren't required for the model
     *
     * @var array
     */
    protected $importsNotUsed = [];

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
     * Generates a model given the model data provided
     *
     * @param array $data
     * @return bool
     * @throws ModelFileAlreadyExistsException
     */
    public function write(array $data)
    {
        $this->reset();

        $name = $this->makeFqnForModelName( array_get($data, 'name') );

        $this->data    = $data;
        $this->fqnName = $name;


        if (empty($name)) {
            throw new InvalidArgumentException("Empty name for module, check the data parameter");
        }

        $path = $this->getPath($name);

        if ($this->alreadyExists($name)) {
            throw new ModelFileAlreadyExistsException("Model with name {$name} already exists");
        }

        $this->makeDirectory($path);

        $this->files->put($path, $this->buildClass());
    }

    /**
     * Resets the class memory for a new writing action
     */
    protected function reset()
    {
        $this->data = null;

        $this->importsNotUsed = [];
    }

    /**
     * Build Fully Qualified Namespace for a model name
     *
     * @param string $name
     * @return string
     */
    protected function makeFqnForModelName($name)
    {
        return config('pxlcms.generator.namespace.models') . studly_case($name);
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

        return $this->laravel['path'].'/'.str_replace('\\', '/', $name).'.php';
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
     * Performs all replaces in the stub content
     *
     * @param  string  $stub
     * @return string
     */
    protected function doReplaces($stub)
    {
        $name = $this->data['name'];

        $class = str_replace($this->getNamespace($name) . '\\', '', $name);

        $stub = str_replace('{{MODEL_CLASSNAME}}', studly_case($class), $stub);
        $stub = str_replace('{{NAMESPACE}}', $this->getNamespace($this->fqnName), $stub);
        //$stub = str_replace('DummyRootNamespace', $this->laravel->getNamespace(), $stub);

        $table = array_get($this->data, 'table');
        $stub = preg_replace(
            '# *{{TABLE}}\n?#i',
            $table ? "\n" . str_repeat(' ', 4) . "protected \$table = '" . $table . "';\n" : '',
            $stub
        );

        $stub = preg_replace('# *{{USETRAITS}}\n?#i', $this->getTraitsStubReplace(), $stub);
        $stub = preg_replace('#{{USEIMPORTS}}\n?#i', $this->getImportsStubReplace(), $stub);

        $stub = str_replace('{{MODULE_NUMBER}}', array_get($this->data, 'module'), $stub);

        $stub = preg_replace('# *{{FILLABLE}}\n?#i', $this->getFillableStubReplace(), $stub);
        $stub = preg_replace('# *{{TRANSLATED}}\n?#i', $this->getTranslatedStubReplace(), $stub);
        $stub = preg_replace('# *{{HIDDEN}}\n?#i', $this->getHiddenStubReplace(), $stub);
        $stub = preg_replace('# *{{CASTS}}\n?#i', $this->getCastsStubReplace(), $stub);
        $stub = preg_replace('# *{{DATES}}\n?#i', $this->getDatesStubReplace(), $stub);
        $stub = preg_replace('# *{{RELATIONSCONFIG}}\n?#i', $this->getRelationsConfigStubReplace(), $stub);

        $stub = preg_replace('# *{{RELATIONSHIPS}}\n?#i', $this->getRelationshipsStubReplace(), $stub);


        return $stub;
    }


    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return __DIR__.'/stubs/model.stub';
    }


    /**
     * Returns the replacement for the use traits placeholder
     *
     * @return string
     */
    protected function getTraitsStubReplace()
    {
        $traits = [];

        if (array_get($this->data, 'is_translated')) {
            $traits[] = 'Translatable';
        } else {
            $this->importsNotUsed[] = static::IMPORT_TRAIT_TRANSLATABLE;
        }

        if (array_get($this->data, 'is_listified')) {
            $traits[] = 'Listify';
            $traits[] = 'ListifyConstructorTrait';
        } else {
            $this->importsNotUsed[] = static::IMPORT_TRAIT_LISTIFY;
        }

        if ( ! count($traits)) return '';

        $lastIndex = count($traits) - 1;

        $replace = str_repeat(' ', 4) . 'use ';

        foreach ($traits as $index => $trait) {

            $replace .= ($index > 0 ? str_repeat(' ', 8) : null)
                      . $trait
                      . ($index == $lastIndex ? ";\n" : ',')
                      . "\n";
        }

        return $replace;
    }

    /**
     * Returns the replacement for the use use-imports placeholder
     *
     * @return string
     */
    protected function getImportsStubReplace()
    {
        $imports = array_diff(
            [
                static::IMPORT_TRAIT_LISTIFY,
                static::IMPORT_TRAIT_TRANSLATABLE
            ],
            $this->importsNotUsed
        );

        $replace = "\nuse Czim\\PxlCms\\Models\\CmsModel;\n";

        if (in_array(static::IMPORT_TRAIT_LISTIFY, $imports)) {
            $replace .= "use Czim\\PxlCms\\Models\\ListifyConstructorTrait;\n";
        }

        if (in_array(static::IMPORT_TRAIT_TRANSLATABLE, $imports)) {
            $replace .= "use Czim\\PxlCms\\Translatable\\Translatable;\n";
        }

        if (in_array(static::IMPORT_TRAIT_LISTIFY, $imports)) {
            $replace .= "use Lookitsatravis\\Listify\\Listify;\n";
        }

        $replace .= "\n";

        return $replace;
    }

    /**
     * Returns the replacement for the fillable placeholder
     *
     * @return string
     */
    protected function getFillableStubReplace()
    {
        $attributes = array_merge(
            array_get($this->data, 'normal_fillable'),
            array_get($this->data, 'translated_fillable')
        );

        if ( ! count($attributes)) return '';

        $replace = str_repeat(' ', 4) ."protected \$fillable = [\n";

        foreach ($attributes as $fillable) {
            $replace .= str_repeat(' ', 8) . "'" . $fillable . "',\n";
        }

        $replace .= str_repeat(' ', 4) . "];\n\n";

        return $replace;
    }

    /**
     * Returns the replacement for the translated placeholder
     *
     * @return string
     */
    protected function getTranslatedStubReplace()
    {
        $attributes = array_get($this->data, 'translated_attributes') ?: [];

        if ( ! count($attributes)) return '';

        $replace = str_repeat(' ', 4) . "protected \$translatedAttributes = [\n";

        foreach ($attributes as $attribute) {
            $replace .= str_repeat(' ', 8) . "'" . $attribute . "',\n";
        }

        $replace .= str_repeat(' ', 4) . "];\n\n";

        return $replace;
    }

    /**
     * Returns the replacement for the hidden placeholder
     *
     * @return string
     */
    protected function getHiddenStubReplace()
    {
        $attributes = array_get($this->data, 'hidden') ?: [];

        if ( ! count($attributes)) return '';

        $replace = str_repeat(' ', 4) . "protected \$hidden = [\n";

        foreach ($attributes as $attribute) {
            $replace .= str_repeat(' ', 8) . "'" . $attribute . "',\n";
        }

        $replace .= str_repeat(' ', 4) . "];\n\n";

        return $replace;
    }

    /**
     * Returns the replacement for the casts placeholder
     *
     * @return string
     */
    protected function getCastsStubReplace()
    {
        $attributes = array_get($this->data, 'casts') ?: [];

        if ( ! count($attributes)) return '';

        $replace = str_repeat(' ', 4) . "protected \$casts = [\n";

        foreach ($attributes as $attribute => $type) {
            $replace .= str_repeat(' ', 8) . "'" . $attribute . "' => '" . $type . "',\n";
        }

        $replace .= str_repeat(' ', 4) . "];\n\n";

        return $replace;
    }

    /**
     * Returns the replacement for the dates placeholder
     *
     * @return string
     */
    protected function getDatesStubReplace()
    {
        $attributes = array_get($this->data, 'dates') ?: [];

        if ( ! count($attributes)) return '';

        $replace = str_repeat(' ', 4) . "protected \$dates = [\n";

        foreach ($attributes as $attribute) {
            $replace .= str_repeat(' ', 8) . "'" . $attribute . "',\n";
        }

        $replace .= str_repeat(' ', 4) . "];\n\n";

        return $replace;
    }

    /**
     * Returns the replacement for the relations config placeholder
     *
     * @return string
     */
    protected function getRelationsConfigStubReplace()
    {
        // only for many to many relationships
        $relationships = [];

        foreach (array_get($this->data, 'relationships.normal') as $name => $relationship) {
            if ($relationship['type'] != Generator::RELATIONSHIP_BELONGS_TO_MANY) continue;

            $relationship['reverse'] = true;
            $relationships[ $name ]  = $relationship;
        }

        foreach (array_get($this->data, 'relationships.reverse') as $name => $relationship) {
            if ($relationship['type'] != Generator::RELATIONSHIP_BELONGS_TO_MANY) continue;

            $relationship['reverse'] = false;
            $relationships[ $name ]  = $relationship;
        }

        if ( ! count($relationships)) return '';

        $replace = str_repeat(' ', 4) . "protected \$relationsConfig = [\n";

        foreach ($relationships as $name => $relationship) {
            $replace .= str_repeat(' ', 8) . "'" . $name . "' => [\n"
                      . str_repeat(' ', 12) . "'field'  => " . $relationship['field'] . ",\n"
                      . str_repeat(' ', 12) . "'parent' => " . ($relationship['reverse'] ? 'false' : 'true') . ",\n"
                      . str_repeat(' ', 8) . "],\n";
        }

        $replace .= str_repeat(' ', 4) . "];\n\n";

        return $replace;
    }

    /**
     * Returns the replacement for the relationships placeholder
     *
     * @return string
     */
    protected function getRelationshipsStubReplace()
    {
        $relationships = array_merge(
            array_get($this->data, 'relationships.normal'),
            array_get($this->data, 'relationships.reverse')
        );

        $totalCount = count($relationships)
                    + count( array_get($this->data, 'relationships.image') )
                    + count( array_get($this->data, 'relationships.file') )
                    + count( array_get($this->data, 'relationships.checkbox') );

        if ( ! $totalCount) return '';

        $replace = "\n" . str_repeat(' ', 4) . "/*\n"
                 . str_repeat(' ', 4) . " * Relationships\n"
                 . str_repeat(' ', 4) . " */\n\n";

        foreach ($relationships as $name => $relationship) {

            $relatedClassName = studly_case($this->data['related_models'][ $relationship['model'] ]['name']);

            $relationParameters = '';

            if ($relationKey = array_get($relationship, 'key')) {
                $relationParameters = ", '{$relationKey}'";
            }

            $replace .= str_repeat(' ', 4) . "public function {$name}()\n"
                      . str_repeat(' ', 4) . "{\n"
                      . str_repeat(' ', 8) . "return \$this->{$relationship['type']}({$relatedClassName}::class"
                      . $relationParameters
                      . ");\n"
                      . str_repeat(' ', 4) . "}\n"
                      . "\n";
        }

        return $replace;
    }


}
