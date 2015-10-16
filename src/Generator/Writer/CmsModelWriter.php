<?php
namespace Czim\PxlCms\Generator\Writer;

use Czim\PxlCms\Generator\Exceptions\ModelFileAlreadyExistsException;
use Czim\PxlCms\Generator\Generator;
use Czim\PxlCms\Models\CmsModel;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CmsModelWriter
{
    const IMPORT_TRAIT_LISTIFY      = 'listify';
    const IMPORT_TRAIT_REMEMBERABLE = 'rememberable';
    const IMPORT_TRAIT_TRANSLATABLE = 'translatable';

    const STANDARD_MODEL_FILE     = 'file';
    const STANDARD_MODEL_IMAGE    = 'image';
    const STANDARD_MODEL_CHECKBOX = 'checkbox';

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
     * Whether the rememberable trait is set on the model
     *
     * @var bool
     */
    protected $blockRememberableTrait = false;

    /**
     * Which of the standard special relationships models were used
     * in this model's context
     *
     * @var array
     */
    protected $standardModelsUsed = [];

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

        if (array_get($data, 'cached') === false) {
            $this->blockRememberableTrait = true;
        }


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
        $this->standardModelsUsed = [];
        $this->blockRememberableTrait = false;
    }

    /**
     * Build Fully Qualified Namespace for a model name
     *
     * @param string $name
     * @return string
     */
    protected function makeFqnForModelName($name)
    {
        return config('pxlcms.generator.namespace.models') . "\\" . studly_case($name);
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
     * Get the model name from a FQN
     *
     * @param string $namespace
     * @return string
     */
    protected function getModelNameFromNamespace($namespace)
    {
        $parts = explode('\\', $namespace);

        return trim($parts[ count($parts) - 1 ]);
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
            $table ? "\n" . $this->tab() . "protected \$table = '" . $table . "';\n" : '',
            $stub
        );

        $stub = preg_replace('# *{{USETRAITS}}\n?#i', $this->getTraitsStubReplace(), $stub);

        $stub = str_replace('{{MODULE_NUMBER}}', array_get($this->data, 'module'), $stub);
        $stub = preg_replace('# *{{TIMESTAMPS}}\n?#i', $this->getTimestampStubReplace(), $stub);

        $stub = preg_replace('# *{{FILLABLE}}\n?#i', $this->getFillableStubReplace(), $stub);
        $stub = preg_replace('# *{{TRANSLATED}}\n?#i', $this->getTranslatedStubReplace(), $stub);
        $stub = preg_replace('# *{{HIDDEN}}\n?#i', $this->getHiddenStubReplace(), $stub);
        $stub = preg_replace('# *{{CASTS}}\n?#i', $this->getCastsStubReplace(), $stub);
        $stub = preg_replace('# *{{DATES}}\n?#i', $this->getDatesStubReplace(), $stub);
        $stub = preg_replace('# *{{RELATIONSCONFIG}}\n?#i', $this->getRelationsConfigStubReplace(), $stub);

        $stub = preg_replace('# *{{RELATIONSHIPS}}\n?#i', $this->getRelationshipsStubReplace(), $stub);

        // has to be last, because only then do we know what to include
        $stub = preg_replace('#{{USEIMPORTS}}\n?#i', $this->getImportsStubReplace(), $stub);

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

        if ( ! $this->blockRememberableTrait) {
            $traits[] = 'Rememberable';
        } else {
            $this->importsNotUsed[] = static::IMPORT_TRAIT_REMEMBERABLE;
        }

        if ( ! count($traits)) return '';

        $lastIndex = count($traits) - 1;

        $replace = $this->tab() . 'use ';

        foreach ($traits as $index => $trait) {

            $replace .= ($index > 0 ? $this->tab(2) : null)
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
                static::IMPORT_TRAIT_TRANSLATABLE,
                static::IMPORT_TRAIT_REMEMBERABLE,
            ],
            $this->importsNotUsed
        );


        // build up import lines
        $importLines = [
            "Czim\\PxlCms\\Models\\CmsModel"
        ];

        if (    in_array(static::STANDARD_MODEL_CHECKBOX, $this->standardModelsUsed)
            &&  config('pxlcms.generator.include_namespace_of_standard_models')
        ) {
            $importLines[] = config('pxlcms.generator.standard_models.checkbox');
        }


        if (    in_array(static::STANDARD_MODEL_IMAGE, $this->standardModelsUsed)
            &&  config('pxlcms.generator.include_namespace_of_standard_models')
        ) {
            $importLines[] = config('pxlcms.generator.standard_models.image');
        }

        if (    in_array(static::STANDARD_MODEL_FILE, $this->standardModelsUsed)
            &&  config('pxlcms.generator.include_namespace_of_standard_models')
        ) {
            $importLines[] = config('pxlcms.generator.standard_models.file');
        }

        if (in_array(static::IMPORT_TRAIT_LISTIFY, $imports)) {
            $importLines[] = "Czim\\PxlCms\\Models\\ListifyConstructorTrait";
            $importLines[] = "Lookitsatravis\\Listify\\Listify";
        }

        if (in_array(static::IMPORT_TRAIT_TRANSLATABLE, $imports)) {
            $importLines[] = "Czim\\PxlCms\\Translatable\\Translatable";
        }

        if (in_array(static::IMPORT_TRAIT_REMEMBERABLE, $imports)) {
            $importLines[] =  "Watson\\Rememberable\\Rememberable";
        }

        // set them in the right order
        if (config('pxlcms.generator.sort_imports_by_string_length')) {

            // sort from shortest to longest
            usort($importLines, function ($a, $b) {
                return strlen($a) - strlen($b);
            });

        } else {
            sort($importLines);
        }

        // build the actual replacement string
        $replace = "\n";

        foreach ($importLines as $line) {
            $replace .= "use " . $line . ";\n";
        }

        $replace .= "\n";

        return $replace;
    }

    /**
     * Returns the replacement for the timestamp placeholder
     *
     * @return string
     */
    protected function getTimestampStubReplace()
    {
        if (is_null(array_get($this->data, 'timestamps'))) return '';

        return "\n"
                 . $this->tab() ."public \$timestamps = "
                 . (array_get($this->data, 'timestamps') ? 'true' : 'false')
                 .";\n";
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

        return $this->getAttributePropertySection('fillable', $attributes);
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

        return $this->getAttributePropertySection('translatedAttributes', $attributes);
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

        return $this->getAttributePropertySection('hidden', $attributes);
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

        // align assignment signs by longest attribute
        $longestLength = 0;
        foreach ($attributes as $attribute => $type) {
            if (strlen($attribute) > $longestLength) {
                $longestLength = strlen($attribute);
            }
        }

        $replace = $this->tab() . "protected \$casts = [\n";

        foreach ($attributes as $attribute => $type) {

            $replace .= $this->tab(2)
                       . "'" . str_pad($attribute . "'", $longestLength + 1)
                       . " => '" . $type . "',\n";
        }

        $replace .= $this->tab() . "];\n\n";

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

        return $this->getAttributePropertySection('dates', $attributes);
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

        foreach (array_get($this->data, 'relationships.image') as $name => $relationship) {
            $relationship['special']       = CmsModel::RELATION_TYPE_IMAGE;
            $relationship['specialString'] = "self::RELATION_TYPE_IMAGE";
            $relationships[ $name ]  = $relationship;
        }

        foreach (array_get($this->data, 'relationships.file') as $name => $relationship) {
            $relationship['special']       = CmsModel::RELATION_TYPE_FILE;
            $relationship['specialString'] = "self::RELATION_TYPE_FILE";
            $relationships[ $name ]  = $relationship;
        }

        foreach (array_get($this->data, 'relationships.checkbox') as $name => $relationship) {
            $relationship['special']       = CmsModel::RELATION_TYPE_CHECKBOX;
            $relationship['specialString'] = "self::RELATION_TYPE_CHECKBOX";
            $relationships[ $name ]  = $relationship;
        }

        if ( ! count($relationships)) return '';

        $replace = $this->tab() . "protected \$relationsConfig = [\n";

        foreach ($relationships as $name => $relationship) {

            $replace .= $this->tab(2) . "'" . $name . "' => [\n";

            $rows = [
                'field' => $relationship['field'],
            ];

            if (isset($relationship['special'])) {
                $rows['type'] = $relationship['specialString'];
            } else {
                $rows['parent'] = ($relationship['reverse'] ? 'false' : 'true');
            }

            if (isset($relationship['translated']) && $relationship['translated']) {
                $rows['translated'] = 'true';
            }

            $longestPropertyLength = $this->getLongestKey($rows);

            foreach ($rows as $property => $value) {

                $replace .= $this->tab(3) . "'"
                          . str_pad($property . "'", $longestPropertyLength + 1)
                          . " => {$value},\n";
            }


            $replace .= $this->tab(2) . "],\n";
        }

        $replace .= $this->tab() . "];\n\n";

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

        $replace = "\n" . $this->tab() . "/*\n"
                 . $this->tab() . " * Relationships\n"
                 . $this->tab() . " */\n\n";


        /*
         * Normal and Reversed relationships
         */

        foreach ($relationships as $name => $relationship) {

            $relatedClassName = studly_case($this->data['related_models'][ $relationship['model'] ]['name']);

            $relationParameters = '';

            if ($relationKey = array_get($relationship, 'key')) {
                $relationParameters = ", '{$relationKey}'";
            }

            $replace .= $this->tab() . "public function {$name}()\n"
                      . $this->tab() . "{\n"
                      . $this->tab(2) . "return \$this->{$relationship['type']}({$relatedClassName}::class"
                      . $relationParameters
                      . ");\n"
                      . $this->tab() . "}\n"
                      . "\n";
        }

        /*
         * Images
         */

        $imageRelationships = array_get($this->data, 'relationships.image');

        if (count($imageRelationships)) {
            $this->standardModelsUsed[] = static::STANDARD_MODEL_IMAGE;
        }

        foreach ($imageRelationships as $name => $relationship) {

            if (config('pxlcms.generator.include_namespace_of_standard_models')) {
                $relatedClassName = $this->getModelNameFromNamespace(config('pxlcms.generator.standard_models.image'));
            } else {
                $relatedClassName = '\\' . config('pxlcms.generator.standard_models.image');
            }

            $relationParameters = '';

            if ($relationKey = array_get($relationship, 'key')) {
                $relationParameters = ", '{$relationKey}'";
            }

            $replace .= $this->tab() . "public function {$name}()\n"
                      . $this->tab() . "{\n"
                      . $this->tab(2) . "return \$this->{$relationship['type']}({$relatedClassName}::class"
                      . $relationParameters
                      . ");\n"
                      . $this->tab() . "}\n"
                      . "\n";

            // since images require special attention for resize enrichment,
            // add an accessor method that will take care of it (through some magic)
            $replace .= $this->tab() . "public function get" . studly_case($name) . "Attribute()\n"
                      . $this->tab() . "{\n"
                      . $this->tab(2) . "return \$this->getImagesWithResizes();\n"
                      . $this->tab() . "}\n"
                      . "\n";
        }

        /*
         * Files
         */

        $fileRelationships = array_get($this->data, 'relationships.file');

        if (count($fileRelationships)) {
            $this->standardModelsUsed[] = static::STANDARD_MODEL_FILE;
        }

        foreach ($fileRelationships as $name => $relationship) {

            if (config('pxlcms.generator.include_namespace_of_standard_models')) {
                $relatedClassName = $this->getModelNameFromNamespace(config('pxlcms.generator.standard_models.file'));
            } else {
                $relatedClassName = '\\' . config('pxlcms.generator.standard_models.file');
            }

            $relationParameters = '';

            if ($relationKey = array_get($relationship, 'key')) {
                $relationParameters = ", '{$relationKey}'";
            }

            $replace .= $this->tab() . "public function {$name}()\n"
                      . $this->tab() . "{\n"
                      . $this->tab(2) . "return \$this->{$relationship['type']}({$relatedClassName}::class"
                      . $relationParameters
                      . ");\n"
                      . $this->tab() . "}\n"
                      . "\n";
        }

        /*
         * Checkboxes
         */

        $checkboxRelationships = array_get($this->data, 'relationships.checkbox');

        if (count($checkboxRelationships)) {
            $this->standardModelsUsed[] = static::STANDARD_MODEL_CHECKBOX;
        }

        foreach ($checkboxRelationships as $name => $relationship) {

            if (config('pxlcms.generator.include_namespace_of_standard_models')) {
                $relatedClassName = $this->getModelNameFromNamespace(config('pxlcms.generator.standard_models.checkbox'));
            } else {
                $relatedClassName = '\\' . config('pxlcms.generator.standard_models.checkbox');
            }

            $relationParameters = '';

            if ($relationKey = array_get($relationship, 'key')) {
                $relationParameters = ", '{$relationKey}'";
            }

            $replace .= $this->tab() . "public function {$name}()\n"
                      . $this->tab() . "{\n"
                      . $this->tab(2) . "return \$this->{$relationship['type']}({$relatedClassName}::class"
                      . $relationParameters
                      . ");\n"
                      . $this->tab() . "}\n"
                      . "\n";
        }

        return $replace;
    }

    /**
     * @param string $variable
     * @param array  $attributes
     * @return string
     */
    protected function getAttributePropertySection($variable, array $attributes)
    {
        $replace = $this->tab() . "protected \${$variable} = [\n";

        foreach ($attributes as $attribute) {
            $replace .= $this->tab(2) . "'" . $attribute . "',\n";
        }

        $replace .= $this->tab() . "];\n\n";

        return $replace;
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
