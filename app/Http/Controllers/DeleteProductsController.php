<?php
namespace App\Http\Controllers;
use DB;

class DeleteProductsController extends Controller
{
    public function deleteProducts()
    {

        $this->service = configMagento();
        $this->service->init();

        
        /*$CurrentTime = date("Y-m-d H:i:s");
        $values =   array('content_id' => '11111',
                        'entity_type' => 'test',
                        'status' => 'test',
                        'message' => 'test',
                        'updated_at' => $CurrentTime,
                        'created_at' => $CurrentTime,
                        'flag' => 0
                    );
        $resultLogId = DB::table('log')->insertGetId($values);*/

        try {
            $result = $this->service->call("products?searchCriteria=100000");
            if(isset($result->total_count) && $result->total_count > 0) {
                foreach ($result->items as $iKey => $itemAr) {
                    echo $sku = $itemAr->sku;
                    if(strstr($sku, '/')){
                        $sku = str_replace("/","%2F",$sku);
                    }
                    if(strstr($sku, ' ')){
                        $sku = str_replace(" ","%20",$sku);
                    }
                    try{
                        $result = $this->service->call('products/'.$sku, '', 'DELETE');
                    } catch (\Throwable $e) {
                        echo $e;
                    }
                }
            }
        } catch (\Throwable $e) {
            $errorMsg = $e;
            echo $e;
        }
    }  
}
