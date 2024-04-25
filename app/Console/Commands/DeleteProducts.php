<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\DeleteProductsController;

class DeleteProducts extends Command
{
    private $productController;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:deleteallmagentoproducts';

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
        $this->deleteProductsController = new DeleteProductsController;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->deleteProductsController->deleteProducts();
        return 0;
    }
}
