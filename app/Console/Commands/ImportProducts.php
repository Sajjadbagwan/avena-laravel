<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ProductsController;

class ImportProducts extends Command
{
    private $productController;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:productdata';

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
        $this->productsController = new ProductsController;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->productsController->syncProduct();
        return 0;
    }
}
