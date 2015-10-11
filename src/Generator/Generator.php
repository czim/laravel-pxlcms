<?php
namespace Czim\PxlCms\Generator;

/**
 * 1. analyze cms content
 *    find all (non-ignored) modules
 *    find all fields per module, store them per type:
 *       normal (with cast type etc)
 *       translated
 *       image / multi-image
 *       file upload(s)
 *       references (one / many / special)
 *          let op dat self-references anders moeten worden verwerkt ...
 *    store information about these modules temporarily
 *    resolve all references between modules
 *
 * 2. determine what models may be written (do not exist yet)
 *  translations separately from models themselves (though that is not perfect)
 *
 * 3. write files
 */
class Generator
{
    const RELATIONSHIP_HAS_ONE         = 'hasOne';
    const RELATIONSHIP_HAS_MANY        = 'hasMany';
    const RELATIONSHIP_BELONGS_TO      = 'belongsTo';
    const RELATIONSHIP_BELONGS_TO_MANY = 'belongsToMany';

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
     * @param bool $write   whether to write files; if false, just outputs analyzed data
     */
    public function __construct($write = true)
    {
        $this->analyzer = new CmsAnalyzer();
        $this->writer   = new ModelWriter();
        $this->write    = (bool) $write;
    }

    /**
     * @return bool
     */
    public function generate()
    {
        $data = $this->analyzer->analyze();

        $log = $this->analyzer->getLog();

        if ( ! empty($log)) {
            $this->logOutput($log);
        }

        if ( ! $this->write) {
            $this->debugOutput($data);
            return true;
        }

        $this->writer->setData($data);
        $this->writer->writeFiles();

        $this->logOutput( $this->writer->getLog() );

        return true;
    }


    protected function debugOutput($data)
    {
        dd($data);
    }

    protected function logOutput(array $log)
    {
        foreach ($log as $logLine) {
            echo $logLine . "\n";
        }
    }

}
