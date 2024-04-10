<?php
namespace App\Http\Controllers;
use App\Http\Controllers\RowDataToCsvController;
use Illuminate\Http\Request;
use DB;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;


class ProductsController extends Controller
{
    public function __construct()
    {
        $this->rowDataToCsvController = New RowDataToCsvController;
    }

    public function syncProduct()
    {
        $MagentoProducts = array();
        //$MagentoProducts = $this->getAllProductsFromMagento();

        $MagentoProducts = array('2064'=>'1565','2065'=>'1566');

        $file = public_path('file/productFilepart.csv');

        $productCsvArr = $this->rowDataToCsvController->csvToArray($file);

        echo '<pre>';
        print_r(array_slice($productCsvArr, 0, 10));
        echo '</pre>';
        die();


        for ($i = 0; $i < count($productCsvArr); $i ++)
        {
            $productSku = $productCsvArr[$i]['ContentID'];
            if (in_array($productSku, $MagentoProducts)){
                echo "Update process for content id : ".$productCsvArr[$i]['ContentID'];;
                echo '<br>';

                $magento_id = array_search($productSku, $MagentoProducts);
                $this->updateCategoryInMagento($magento_id,$productCsvArr[$i]);
            }else{
                echo "Insert process for content id : ".$productCsvArr[$i]['ContentID'];;
                echo '<br>';
                $result = $this->insertProductInMagento($productCsvArr[$i]);
                if(!empty($result)){
                    $MagentoProducts[$result['magento_id']] = $result['magento_sku'];
                }
            }
        }
        /*echo '<pre>'; print_r($MagentoProducts); echo '</pre>';*/
        echo 'All the products has been updated.';
        die();
    }

    function insertProductInMagento($csvCatData){
        
        $resultLogId = '';
        $resultLogId = $this->generateLog($csvCatData['ContentID'],'product');

        $data = $this->getProductData($csvCatData);

        $result = array();
        $this->service = configMagento();
        $this->service->init();
        try {
            $result = $this->service->call('products', $data, 'POST');
        } catch (\Throwable $e) {
            echo 'Failed to insert category to magento for content ID '.$csvCatData['ContentID'];
            echo '<br/>';
            Log::info("Failed to insert category to magento");
            Log::info($e);
        }

        $CurrentTime = date("Y-m-d H:i:s");
        $dataCatTblArr = array();
        $logIdArr=array();
        $returnArr=array();
        $logIdArr['logId'] = $resultLogId;
        if(isset($result->id)){
            $dataCatTblArr['magento_id'] = $result->id;
            $dataCatTblArr['content_id'] = $csvCatData['ContentID'];
            $dataCatTblArr['status'] = 'success';
            $dataCatTblArr['updated_at'] = $CurrentTime;
            $dataCatTblArr['created_at'] = $CurrentTime;
            
            $logIdArr['status'] = 'success';
            $logIdArr['message'] = 'Data has been inserted.';

            $returnArr['magento_id'] = $result->id;
            $returnArr['magento_sku'] = $csvCatData['ContentID'];
        }else{
            $dataCatTblArr['magento_id'] = 0;
            $dataCatTblArr['content_id'] = $csvCatData['ContentID'];
            $dataCatTblArr['status'] = 'failed';
            $dataCatTblArr['updated_at'] = $CurrentTime;
            $dataCatTblArr['created_at'] = $CurrentTime;
            
            $logIdArr['status'] = 'failed';
            $logIdArr['message'] = 'Process failed';
        }
        $this->insertProductTbl($dataCatTblArr);
        $this->updateGeneratedLog($logIdArr);
        
        return $returnArr;
    }

    function updateCategoryInMagento($magento_id,$csvCatData){
        $resultLogId = '';
        $resultLogId = $this->generateLog($csvCatData['ContentID'],'product');
        $data = $this->getProductData($csvCatData);

        echo '<pre>';
        print_r($data);
        echo '</pre>';
        

        $result = array();
        $this->service = configMagento();
        $this->service->init();
        try {
            $restUrl = 'products/'.$csvCatData['ContentID'];
            $result = $this->service->call($restUrl, $data, 'PUT');
        } catch (\Throwable $e) {
            $proceesMsg = 'Failed to update category to magento for content ID '.$csvCatData['ContentID'];
            Log::info("Failed to insert category to magento");
            Log::info($e);
            echo $proceesMsg;
            echo '<br/>';
        }

        $CurrentTime = date("Y-m-d H:i:s");
        $dataCatTblArr = array();
        $logIdArr=array();
        $logIdArr['logId'] = $resultLogId;
        if(isset($result->id)){
            $dataCatTblArr['magento_id'] = $result->id;
            $dataCatTblArr['content_id'] = $csvCatData['ContentID'];
            $dataCatTblArr['status'] = 'success';
            $dataCatTblArr['updated_at'] = $CurrentTime;
            $dataCatTblArr['created_at'] = $CurrentTime;
            
            $logIdArr['status'] = 'success';
            $logIdArr['message'] = 'Data has been updated.';
        }else{
            $dataCatTblArr['magento_id'] = 0;
            $dataCatTblArr['content_id'] = $csvCatData['ContentID'];
            $dataCatTblArr['status'] = 'failed';
            $dataCatTblArr['updated_at'] = $CurrentTime;
            $dataCatTblArr['created_at'] = $CurrentTime;
            
            $logIdArr['status'] = 'failed';
            $logIdArr['message'] = $proceesMsg;
        }

        /*echo '<pre>'; print_r($dataCatTblArr); 
        echo $magento_id;*/

        $this->updateProductTbl($magento_id,$dataCatTblArr);
        $this->updateGeneratedLog($logIdArr);
    }

    public function getProductData($csvCatData){

        $parentContentId = $csvCatData['ParentContentID'];
        $magentoParentId = $this->findMagentoParentId($parentContentId);

        $category_url = $csvCatData['FileName'];
        $urlArr = array_filter(explode('/', $category_url));
        $currentCatUrl = current($urlArr);

        $magentoCategoryIdsArr = $this->getCategoryMagentoIdfromCategoryContentId($csvCatData['category_content_id']);

        $is_active='false'; if($csvCatData['Enabled']==1){ $is_active='true';}
        $custom_attributes[] = (object)array(
            "attribute_code" => 'url_key',
            "value" => $currentCatUrl
        );
        $custom_attributes[] = (object)array(
            "attribute_code" => 'meta_title',
            "value" => $csvCatData['AdditionalPageTitle']
        );
        $custom_attributes[] = (object)array(
            "attribute_code" => 'meta_keywords',
            "value" => $csvCatData['MetaKeywords']
        );
        $custom_attributes[] = (object)array(
            "attribute_code" => 'meta_description',
            "value" => $csvCatData['MetaDescription']
        );
        if(!empty($magentoCategoryIdsArr)){
            $custom_attributes[] = (object)array(
                "attribute_code" => 'category_ids',
                "value" => $magentoCategoryIdsArr
            );
        }

        $productData = (object)array(
            "sku" => $csvCatData['ContentID'],
            "name" => $csvCatData['PageTitle'],
            "price" => $csvCatData['tbl_Product_Price'],
            "status" => 1,
            "type_id" => "simple",
            "attribute_set_id" => 4,
            "custom_attributes" => $custom_attributes
        );
        $magentoData = (object)array('product' => $productData);
        $data = $magentoData;

        return $data;
    }

    public function getCategoryMagentoIdfromCategoryContentId($contentid){
        $contentIdArray = explode("|",$contentid);
        $catMagentoIdArr = array();
        if(!empty($contentIdArray)){
            foreach($contentIdArray as $key => $value){
                $existingData = DB::table('category')->where('data_category_id', '=', $value)->orderBy('id', 'desc')->get()->first();
                if(!empty($existingData)){
                    array_push($catMagentoIdArr,$existingData->magento_category_id);
                }
            }
        }
        return $catMagentoIdArr;
    }

    public function generateLog($contentid, $type){
        $CurrentTime = date("Y-m-d H:i:s");
        $values =   array('content_id' => $contentid,
                        'entity_type' => $type,
                        'status' => '',
                        'message' => 'Data being proceed',
                        'updated_at' => $CurrentTime,
                        'created_at' => $CurrentTime,
                        'flag' => 1
                    );
        $resultLogId = DB::table('log')->insertGetId($values);
        return $resultLogId;
    }

    public function updateGeneratedLog($logIdArr){
        DB::table('log')->where('id', $logIdArr['logId'])->update(['status' => $logIdArr['status'],'message' => $logIdArr['message'],'flag' => 0]);
        return;
    }

    public function insertProductTbl($dataCatTblArr){
        $values =   array('data_product_id' => $dataCatTblArr['content_id'],
                        'magento_product_id' => $dataCatTblArr['magento_id'],
                        'status' => $dataCatTblArr['status'],
                        'updated_at' => $dataCatTblArr['updated_at'],
                        'created_at' => $dataCatTblArr['created_at']
                    );
        DB::table('product')->insert($values);
        return;
    }

    public function updateProductTbl($magentoId,$dataCatTblArr){

        $existingData = DB::table('product')->where('magento_product_id', '=', $magentoId)->get()->last();
        if(!empty((array)$existingData)){
            DB::table('product')->where('magento_product_id', $magentoId)->update(['data_product_id' => $dataCatTblArr['content_id'],'status' => $dataCatTblArr['status'],'updated_at' => $dataCatTblArr['updated_at']]);
        }else{
             $values =  array('data_product_id' => $dataCatTblArr['content_id'],
                        'magento_product_id' => $dataCatTblArr['magento_id'],
                        'status' => $dataCatTblArr['status'],
                        'updated_at' => $dataCatTblArr['updated_at'],
                        'created_at' => $dataCatTblArr['created_at']
                    );
            DB::table('product')->insert($values);   
        }   
        return;
    }

    public function getAllProductsFromMagento(){
        $this->service = configMagento();
        $this->service->init();
        try {
            $result = $this->service->call("products?searchCriteria");
            $magentoData = array();
            if(isset($result->total_count) && $result->total_count > 0) {
                foreach ($result->items as $iKey => $itemAr) {
                    $magentoData[$itemAr->id] = $itemAr->sku;
                }
            }
            return $magentoData;
        }catch (\Throwable $e) {
            Log::info("Failed to retrieve categories from magento");
            Log::info($e);
        }
    }

    public function findMagentoParentId($parentContentId){
        $data = DB::table('category')->where('data_category_id', $parentContentId)->orderBy('id', 'desc')->get()->first();
        if(!empty((array)$data)){
            return $data->magento_category_id;
        }else{
            return '2';
        }
        
    }
    
}
