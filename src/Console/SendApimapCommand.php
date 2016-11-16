<?php

namespace ForestAdmin\ForestLaravel\Console;

use Illuminate\Console\Command;
use ForestAdmin\ForestLaravel\Bootstraper;

class SendApimapCommand extends Command {
    protected $signature = 'forest:send-apimap';
    protected $description = 'Send the apimap to Forest';

    public function __construct() {
        parent::__construct();
    }

    public function handle() {
        return (new Bootstraper())->sendApimap();
    }
}
