<?php
namespace App\Http\Controllers;
use App\Http\Controllers\RowDataToCsvController;
use Illuminate\Http\Request;
use DB;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;


class CategoriesController extends Controller
{
    public function __construct()
    {
        $this->rowDataToCsvController = New RowDataToCsvController;
    }

    public function syncCategory()
    {
        $file = public_path('file/categoryFile.csv');
        $catCsvArr = $this->rowDataToCsvController->csvToArray($file);

        $MagentoCategories = array();
        $MagentoCategories = $this->getAllCategoryFromMagento();

        for ($i = 0; $i < count($catCsvArr); $i ++)
        {

            /*echo '<pre>'; print_r($MagentoCategories); echo '</pre>';*/

            $category_url = $catCsvArr[$i]['FileName'];
            if (in_array($category_url, $MagentoCategories)){
                echo "Update process for content id : ".$catCsvArr[$i]['ContentID'];;
                echo '<br>';

                $magento_id = array_search($category_url, $MagentoCategories);
                $this->updateCategoryInMagento($magento_id,$catCsvArr[$i]);
            }else{
                echo "Insert process for content id : ".$catCsvArr[$i]['ContentID'];;
                echo '<br>';
                $result = $this->insertCategoryInMagento($catCsvArr[$i]);
                /*if(!empty($result)){
                    $MagentoCategories[$result['magento_id']] = $result['magento_url'];
                }*/
            }
        }
        echo 'All the categories has been updated.';
        die();
    }

    function insertCategoryInMagento($csvCatData){
        $resultLogId = '';
        $resultLogId = $this->generateLog($csvCatData['ContentID'],'catagory');
        $data = $this->getCategoryData($csvCatData);
        $category_url = $csvCatData['FileName'];

        /*echo '<pre>'; print_r($data); echo '</pre>';*/

        $result = array();
        $this->service = configMagento();
        $this->service->init();
        try {
            $result = $this->service->call('categories', $data, 'POST');
        } catch (\Throwable $e) {
            echo 'Failed to insert category to magento for content ID '.$csvCatData['ContentID'];
            Log::info("Failed to insert category to magento");
            Log::info($e);
            $errorMsg = '';
            $errorMsg = $e;
        }    

        $CurrentTime = date("Y-m-d H:i:s");
        $dataCatTblArr = array();
        $logIdArr=array();
        $returnArr=array();
        $logIdArr['logId'] = $resultLogId;
        if(isset($result->id)){
            $dataCatTblArr['magento_id'] = $result->id;
            $dataCatTblArr['content_id'] = $csvCatData['ContentID'];
            $dataCatTblArr['magento_parent_id'] = $result->parent_id;
            $dataCatTblArr['status'] = 'success';
            $dataCatTblArr['updated_at'] = $CurrentTime;
            $dataCatTblArr['created_at'] = $CurrentTime;
            
            $logIdArr['status'] = 'success';
            $logIdArr['message'] = 'Data has been inserted.';

            $returnArr['magento_id'] = $result->id;
            $returnArr['magento_url'] = $category_url;
        }else{
            $dataCatTblArr['magento_id'] = 0;
            $dataCatTblArr['content_id'] = $csvCatData['ContentID'];
            $dataCatTblArr['magento_parent_id'] = 0;
            $dataCatTblArr['status'] = 'failed';
            $dataCatTblArr['updated_at'] = $CurrentTime;
            $dataCatTblArr['created_at'] = $CurrentTime;
            
            $logIdArr['status'] = 'failed';
            $logIdArr['message'] = $errorMsg;
        }
        $this->insertCatTbl($dataCatTblArr);
        $this->updateGeneratedLog($logIdArr);
        
        return $returnArr;
    }

    function updateCategoryInMagento($magento_id,$csvCatData){
        $resultLogId = '';
        $resultLogId = $this->generateLog($csvCatData['ContentID'],'catagory');
        $data = $this->getCategoryData($csvCatData);

        $result = array();
        $this->service = configMagentoUpdate();
        $this->service->init();
        try {
            $restUrl = 'categories/'.$magento_id;
            $result = $this->service->call($restUrl, $data, 'PUT');
        } catch (\Throwable $e) {
            $proceesMsg = 'Failed to update category to magento for content ID '.$csvCatData['ContentID'];
            Log::info("Failed to insert category to magento");
            Log::info($e);
            echo $proceesMsg;
            $errorMsg = '';
            $errorMsg = $e;
        }    

        $CurrentTime = date("Y-m-d H:i:s");
        $dataCatTblArr = array();
        $logIdArr=array();
        $logIdArr['logId'] = $resultLogId;
        if(isset($result->id)){
            $dataCatTblArr['magento_id'] = $result->id;
            $dataCatTblArr['content_id'] = $csvCatData['ContentID'];
            $dataCatTblArr['magento_parent_id'] = $result->parent_id;
            $dataCatTblArr['status'] = 'success';
            $dataCatTblArr['updated_at'] = $CurrentTime;
            $dataCatTblArr['created_at'] = $CurrentTime;
            
            $logIdArr['status'] = 'success';
            $logIdArr['message'] = 'Data has been updated.';
        }else{
            $dataCatTblArr['magento_id'] = 0;
            $dataCatTblArr['content_id'] = $csvCatData['ContentID'];
            $dataCatTblArr['magento_parent_id'] = 0;
            $dataCatTblArr['status'] = 'failed';
            $dataCatTblArr['updated_at'] = $CurrentTime;
            $dataCatTblArr['created_at'] = $CurrentTime;
            
            $logIdArr['status'] = 'failed';
            $logIdArr['message'] = $errorMsg;
        }
        $this->updateCatTbl($magento_id,$dataCatTblArr);
        $this->updateGeneratedLog($logIdArr);
    }

    public function getCategoryData($csvCatData){

        $parentContentId = $csvCatData['ParentContentID'];
        $magentoParentId = $this->findMagentoParentId($parentContentId);

        $category_url = $csvCatData['FileName'];
        $urlArr = array_filter(explode('/', $category_url));
        $currentCatUrl = end($urlArr);

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

        $categoryData = (object)array(
            "name" => $csvCatData['PageTitle'],
            "parent_id" => $magentoParentId,
            "is_active" => $is_active,
            "include_in_menu" => true,
            "custom_attributes" => $custom_attributes
        );
        $magentoData = (object)array('category' => $categoryData);
        $data = $magentoData;
        return $data;
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

    public function insertCatTbl($dataCatTblArr){
        $values =   array('data_category_id' => $dataCatTblArr['content_id'],
                        'magento_category_id' => $dataCatTblArr['magento_id'],
                        'magento_parent_id' => $dataCatTblArr['magento_parent_id'],
                        'status' => $dataCatTblArr['status'],
                        'updated_at' => $dataCatTblArr['updated_at'],
                        'created_at' => $dataCatTblArr['created_at']
                    );
        DB::table('category')->insert($values);
        return;
    }

    public function updateCatTbl($magentoId,$dataCatTblArr){

        $existingData = DB::table('category')->where('magento_category_id', '=', $magentoId)->get()->last();
        if(!empty((array)$existingData)){
            DB::table('category')->where('magento_category_id', $magentoId)->update(['data_category_id' => $dataCatTblArr['content_id'], 'magento_parent_id' => $dataCatTblArr['magento_parent_id'],'status' => $dataCatTblArr['status'],'updated_at' => $dataCatTblArr['updated_at']]);
        }else{
            $values =   array('data_category_id' => $dataCatTblArr['content_id'],
                        'magento_category_id' => $dataCatTblArr['magento_id'],
                        'magento_parent_id' => $dataCatTblArr['magento_parent_id'],
                        'status' => $dataCatTblArr['status'],
                        'updated_at' => $dataCatTblArr['updated_at'],
                        'created_at' => $dataCatTblArr['created_at']
                    );
            DB::table('category')->insert($values);  
        }
        return;
    }

    public function getAllCategoryFromMagento(){
        $this->service = configMagento();
        $this->service->init();
        try {
            $result = $this->service->call("categories/list?searchCriteria=1000");

            $magentoData = array();
            if(isset($result->total_count) && $result->total_count > 0) {
                $removeValuesIdsAr = array();
                foreach ($result->items as $iKey => $itemAr) {
                    $url_key = '';
                    if($itemAr->id!=1 && $itemAr->id!=2){
                        foreach($itemAr->custom_attributes as $key => $value){
                            if($value->attribute_code == 'url_path'){
                                $url_key = '/'.$value->value.'/';
                            }
                        }
                    }else{
                        $url_key = $itemAr->name;
                    }
                    $magentoData[$itemAr->id] = $url_key;
                }
            }
            return $magentoData;
        }catch (\Throwable $e) {
            echo $e;
            Log::info("Failed to retrieve categories from magento");
            Log::info($e);
        }
    }

    public function findMagentoParentId($parentContentId){
        $data = DB::table('category')->where('data_category_id', $parentContentId)->get()->last();
        if(!empty((array)$data)){
            return $data->magento_category_id;
        }else{
            return '2';
        }
        
    }
    
}
