<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\CategoriesImageController;

class ImportCategoryImage extends Command
{
    private $productController;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:categoryImage';

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
        $this->categoriesImageController = new CategoriesImageController;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->categoriesImageController->syncCategoryImage();
        return 0;
    }
}
