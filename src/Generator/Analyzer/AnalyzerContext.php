<?php
namespace Czim\PxlCms\Generator\Analyzer;

use Czim\DataObject\Contracts\DataObjectInterface;
use Czim\Processor\Contexts\AbstractProcessContext;
use Czim\PxlCms\Generator\FieldType;
use Czim\PxlCms\Generator\Generator;
use Illuminate\Console\Command;

class AnalyzerContext extends AbstractProcessContext
{
    /**
     * FieldType Helper
     *
     * @var FieldType
     */
    public $fieldType;

    /**
     * Analyzer result output
     *
     * @var array
     */
    public $output = [
        'models',
    ];

    /**
     * The console command that initiated the process
     *
     * @var Command|null
     */
    public $command;

    /**
     * Whether the CMS has a 'typical' slug structure setup
     *
     * @var bool
     */
    public $slugStructurePresent = false;

    /**
     * Whether the CMS is considered to have Dutch named modules
     *
     * @var bool
     */
    public $dutchNames = false;

    /**
     * Whether Dutch Mode is enabled
     *
     * @var bool
     */
    public $dutchMode = false;


    /**
     * @param DataObjectInterface $data
     * @param array|null          $settings
     */
    public function __construct(DataObjectInterface $data, array $settings = null)
    {
        parent::__construct($data, $settings);

        $this->fieldType = new FieldType();
    }

    /**
     * Returns whether there is user interaction available
     *
     * @return array
     */
    public function isInteractive()
    {
        if (is_null($this->command)) return false;

        return ! (bool) $this->command->option('auto');
    }

    /**
     * @param string $message
     * @param string $level
     */
    public function log($message, $level = Generator::LOG_LEVEL_DEBUG)
    {
        event('pxlcms.logmessage', [ 'message' => $message, 'level' => $level ]);
    }


    /**
     * Normalizes a name to use as a database table or field reference
     *
     * @param string $name
     * @return string
     */
    public function normalizeNameForDatabase($name)
    {
        return trim(snake_case(preg_replace('#\s+#', '_', $name)), '_');
    }

    /**
     * Normalizes a string (name) to a database field or table name in the
     * same way the PXL CMS does this.
     *
     * @param string $string
     * @param string $spaceSubstitute   what to replace spaces with
     * @return string
     */
    public function normalizeCmsDatabaseString($string, $spaceSubstitute = '_')
    {
        $string = html_entity_decode($string);

        $string = mb_convert_encoding($string, "ISO-8859-1", 'UTF-8');

        $string = preg_replace('#\s+#', $spaceSubstitute, $string);

        $string = $this->normalizeCmsAccents($string);

        $string = preg_replace('#[^0-9a-z' . preg_quote($spaceSubstitute) . ']#i', '', $string);
        $string = preg_replace('#[' . preg_quote($spaceSubstitute) . ']+#', $spaceSubstitute, $string);

        $string = preg_replace('#\s+#', $spaceSubstitute, $string);

        $string = trim($string, $spaceSubstitute);

        return $string;
    }

    /**
     * Normalize accents just like the CMS does
     *
     * @param string $string
     * @return string
     */
    public function normalizeCmsAccents($string)
    {
        $string = htmlentities($string, ENT_COMPAT | (defined('ENT_HTML401') ? ENT_HTML401 : 0), 'ISO-8859-1');

        $string = preg_replace('#&([a-zA-Z])(uml|acute|grave|circ|tilde|slash|cedil|ring|caron);#','$1',$string);

        $string = preg_replace('#&(ae|AE)lig;#','$1',$string);  // æ to ae
        $string = preg_replace('#&(oe|OE)lig;#','$1',$string);  // Œ to OE
        $string = preg_replace('#&szlig;#','ss',$string);       // ß to ss
        $string = preg_replace('#&(eth|thorn);#','th',$string); // ð and þ to th
        $string = preg_replace('#&(ETH|THORN);#','Th',$string); // Ð and Þ to Th

        return html_entity_decode($string);
    }

}
