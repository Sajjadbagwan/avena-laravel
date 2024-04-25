<?php
namespace App\Http\Controllers;


class DeleteProductsController extends Controller
{
    public function deleteProducts()
    {
        $this->service = configMagento();
        $this->service->init();
        try {
            $result = $this->service->call("products?searchCriteria=100000");
            if(isset($result->total_count) && $result->total_count > 0) {
                foreach ($result->items as $iKey => $itemAr) {
                    $sku = $itemAr->sku;
                    try{
                        $result = $this->service->call('products/'.$sku, '', 'DELETE');
                    } catch (\Throwable $e) {
                        echo $e;
                    }
                }
            }
        } catch (\Throwable $e) {
            $errorMsg = $e;
            Log::info("Failed to get magento products.");
        }
    }  
}
