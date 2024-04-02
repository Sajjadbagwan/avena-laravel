<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\ProductController;

class ImportProduct extends Command
{
    private $productController;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:product';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import CUstomer from magento and add or update into Myblog';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->productController = new ProductController;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->productController->index();
        return 0;
    }
}
