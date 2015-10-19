<?php
namespace Czim\PxlCms\Generator;

use Czim\PxlCms\Generator\Analyzer\AnalyzerData;
use Illuminate\Console\Command;

/**
 * This Generator completes the full process of analyzing CMS content,
 * doing checks and overrides and finally writing the source code files
 * that it is configured to.
 *
 * 1. Analyze cms content
 *    store information about these modules temporarily
 *    resolve all references between modules
 *
 * 2. Determine what models may be written (do not exist yet)
 *      translations separately from models themselves (though that is not perfect)
 *
 * 3. Write files
 */
class Generator
{
    const RELATIONSHIP_HAS_ONE         = 'hasOne';
    const RELATIONSHIP_HAS_MANY        = 'hasMany';
    const RELATIONSHIP_BELONGS_TO      = 'belongsTo';
    const RELATIONSHIP_BELONGS_TO_MANY = 'belongsToMany';

    const LOG_LEVEL_DEBUG   = 'debug';
    const LOG_LEVEL_ERROR   = 'error';
    const LOG_LEVEL_INFO    = 'info';
    const LOG_LEVEL_WARNING = 'warning';

    /**
     * @var CmsAnalyzer
     */
    protected $analyzer;

    /**
     * @var ModelWriter
     */
    protected $writer;

    /**
     * Whether to write output files
     *
     * @var bool
     */
    protected $write = true;

    /**
     * The console command that called the generator
     *
     * @var Command     null if not called by console
     */
    protected $command;

    /**
     * @param bool    $write    whether to write files; if false, just outputs analyzed data
     * @param Command $command  the console command, if it was called from console
     */
    public function __construct($write = true, Command $command = null)
    {
        $this->write    = (bool) $write;
        $this->command  = $command;

        $this->analyzer = new CmsAnalyzer($command);
        $this->writer   = new ModelWriter();
    }

    /**
     * @return bool
     */
    public function generate()
    {
        // run analyzer pipeline
        $data = $this->analyzer->process(new AnalyzerData);

        if ( ! $this->write) {
            $this->debugOutput($data->output);
            return true;
        }

        $this->writer->setData($data->output);
        $this->writer->writeFiles();

        return true;
    }


    /**
     * Output for debugging
     *
     * @param mixed $data
     */
    protected function debugOutput($data)
    {
        dd($data);
    }

}
