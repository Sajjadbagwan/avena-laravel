<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\CategoriesController;

class ImportProduct extends Command
{
    private $productController;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:category';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import categories to magento and add or update from Myblog';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->categoriesController = new CategoriesController;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->categoriesController->syncCategory();
        return 0;
    }
}
