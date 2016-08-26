<?php

namespace ForestAdmin\ForestLaravel\Http\Console\Commands;


use ForestAdmin\ForestLaravel\DatabaseStructure;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Symfony\Component\ClassLoader\ClassMapGenerator;
use ForestAdmin\Liana\Model\Collection as ForestCollection;
use ForestAdmin\Liana\Model\Field as ForestField;
use ForestAdmin\Liana\Model\Pivot as ForestPivot;


class PostMapCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'forest:postmap';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Extract the structure of the database from the models';

    /**
     * Array containing the directories where to search for models
     *
     * @var array
     */
    protected $dirs = array();

    /**
     * Create a new command instance.
     *
     */
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
        // TODO: Include the options and argument so the command is more flexible
        $this->dirs = array_merge(
            Config::get('forest.ModelLocations')
//            $this->option('dir')
        );

        $dbstruct = new DatabaseStructure($this->dirs, $this);

        $dbstruct->setCollections($dbstruct->generateCollections());

        return true;
    }
}
