<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\CmspagesController;

class ImportCmspages extends Command
{
    private $productController;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:cmspages';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import cms pages to magento and add or update from Myblog';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->cmspagesController = new CmspagesController;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->cmspagesController->syncCmspages();
        return 0;
    }
}
