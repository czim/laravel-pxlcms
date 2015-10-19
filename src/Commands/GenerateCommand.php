<?php
namespace Czim\PxlCms\Commands;

use Czim\PxlCms\Generator\Generator;
use Illuminate\Console\Command;
use Event;

class GenerateCommand extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pxlcms:generate
                                {--dry-run : Analyzes and shows debug output, but does not write files }
                                {--models-only : Only generates models}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate app files based on PXL CMS database content.';


    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $modelsOnly = (bool) $this->option('models-only');
        $dryRun     = (bool) $this->option('dry-run');

        $this->listenForLogEvents();

        $generator = new Generator( ! $dryRun, $this);

        $generator->generate();

        // todo: handle logging output for cli

        $this->info('Done.');
    }

    protected function listenForLogEvents()
    {
        $verbose = $this->option('verbose');

        Event::listen('pxlcms.logmessage', function($message, $level) use ($verbose) {

            switch ($level) {

                case Generator::LOG_LEVEL_DEBUG:
                    if ($verbose) {
                        $this->line($message);
                    }
                    break;

                case Generator::LOG_LEVEL_WARNING:
                    $this->comment($message);
                    break;

                case Generator::LOG_LEVEL_ERROR:
                    $this->error($message);
                    break;

                case Generator::LOG_LEVEL_INFO:
                default:
                    $this->info($message);
            }
        });
    }
}
