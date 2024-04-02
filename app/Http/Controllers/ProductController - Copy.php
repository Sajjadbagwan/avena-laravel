<?php
namespace App\Http\Controllers;
use App\Http\Controllers\ItemController;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->itemController = New ItemController;
    }
    public function syncProducts()
    {
        $this->itemController->getItems();
        //$this->updateProductItem();
        //$this->addProductItem();

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://magento244.dev-box.me/rest/V1/products/B201-SKU',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'PUT',
            CURLOPT_POSTFIELDS =>'{
                "product": {
                    "price": 90.00,
                    "extension_attributes": {
                        "stock_item": {
                            "qty": 60
                        }
                    }
                }
            }',
            CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer m998dqcngdsshiau1mdztzdygyp3lp5c',
            'Cookie: PHPSESSID=b124f523be799dd84f493a300947a43a'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        echo $response;
        
        echo 'syncProducts done.';        

    }
    public function updateProductItem(){
        
    }
    public function addProductItem(){
        $curl = curl_init();
        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://magento244.dev-box.me/rest/default/V1/products',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS =>'{
          "product": {
            "sku": "new-test-added1",
            "name": "New Test 1",
            "price": 300.00,
            "status": 1,
            "type_id": "simple",
            "attribute_set_id": 4,
            "extension_attributes": {
                "website_ids": [
                    1
                ],
                "stock_item": {
                    "stock_id": 1,
                    "qty": 20
                }
            }
          }
        }',
          CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json',
            'Authorization: Bearer m998dqcngdsshiau1mdztzdygyp3lp5c',
            'Cookie: PHPSESSID=b124f523be799dd84f493a300947a43a'
          ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;

    }
}
