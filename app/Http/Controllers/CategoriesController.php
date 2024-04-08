<?php
namespace App\Http\Controllers;
use App\Http\Controllers\ProductsController;
use Illuminate\Http\Request;
use DB;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;


class CategoriesController extends Controller
{
    public function __construct()
    {
        $this->productsController = New ProductsController;
    }

    public function syncCategory()
    {

        $MagentoCategories = array();
        $MagentoCategories = $this->getAllCategoryFromMagento();

        /*echo '<pre>'; print_r($MagentoCategories); echo '</pre>';*/

        $file = public_path('file/categoryFile.csv');
        $catCsvArr = $this->productsController->csvToArray($file);

        for ($i = 0; $i < count($catCsvArr); $i ++)
        {
            $category_url = $catCsvArr[$i]['FileName'];
            if (in_array($category_url, $MagentoCategories)){
                echo '<br>';
                echo "update";
                echo '<br>';
            }else{
                echo '<br>';
                echo "INSERT";
                echo '<br>';
                $this->insertCategoryInMagento($catCsvArr[$i]);
            }
        }
        echo 'All category updated.';
        die();
    }

    function findMagentoParentId($parentContentId){
        $data = DB::table('category')->where('data_category_id', $parentContentId)->get()->first();
        if(!empty((array)$data)){
            return $data->magento_category_id;
        }else{
            return '2';
        }
        
    }

    function insertCategoryInMagento($csvCatData){
        $resultLogId = '';
        $status = '';
        $result = array();
        $defaultCategory = 2;
        $this->service = configMagento();
        $this->service->init();


        $parentContentId = $csvCatData['ParentContentID'];
        $magentoParentId = $this->findMagentoParentId($parentContentId);

        $resultLogId = $this->generateLog($csvCatData['ContentID'],'catagory');
        /*echo '<pre>'; print_r($csvCatData); echo '</pre>'; die();*/

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

        /*echo '<pre>'; print_r($data); echo '</pre>'; die();*/

        try {
            $result = $this->service->call('categories', $data, 'POST');
        } catch (\Throwable $e) {
            echo 'Failed to insert category to magento for content ID '.$csvCatData['ContentID'];
            Log::info("Failed to insert category to magento");
            Log::info($e);
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
            $logIdArr['message'] = 'Data has been inserted.';
        }else{
            $dataCatTblArr['magento_id'] = 0;
            $dataCatTblArr['content_id'] = $csvCatData['ContentID'];
            $dataCatTblArr['magento_parent_id'] = 0;
            $dataCatTblArr['status'] = 'failed';
            $dataCatTblArr['updated_at'] = $CurrentTime;
            $dataCatTblArr['created_at'] = $CurrentTime;
            
            $logIdArr['status'] = 'failed';
            $logIdArr['message'] = 'Process failed';
        }
        $this->insertCatTbl($dataCatTblArr);
        $this->updateGeneratedLog($logIdArr);
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

    public function getAllCategoryFromMagento(){
        $this->service = configMagento();
        $this->service->init();
        try {
            $result = $this->service->call("categories/list?searchCriteria");
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
            Log::info("Failed to retrieve categories from magento");
            Log::info($e);
        }
    }

    public function checkCategoryIsAvailableInMagento123($category_url){
        try {
            $this->service = configMagento();
            $this->service->init();
            $result = $this->service->call("categories/list?searchCriteria");

            if(isset($result->total_count) && $result->total_count > 0) {
                $removeValuesAr = array("root catalog", "default category");
                $removeValuesIdsAr = array();
                $pathAr = array();
                foreach ($result->items as $iKey => $itemAr) {
                    if(!in_array(strtolower($itemAr->name), $removeValuesAr)) {
                        $pathAr[$itemAr->id] = $itemAr->name;
                        $pathNameAr = array();
                        foreach (explode('/', $itemAr->path) as $pValue) {
                            if(!in_array($pValue, $removeValuesIdsAr) && isset($pathAr[$pValue])) {
                                $pathNameAr[] = $pathAr[$pValue];
                            }
                        }
                        $dataAr = array(
                            'magento_id' => $itemAr->id,
                            'parent_id' => $itemAr->parent_id,
                            'name' => $itemAr->name,
                            'path' => implode('/', array_diff(explode('/', $itemAr->path), $removeValuesIdsAr)),
                            'path_name' => implode(" : ", $pathNameAr)
                        );
                        $this->magentoCategory->insertUpdateCategory($dataAr);
                    } else {
                        $removeValuesIdsAr[] = $itemAr->id;
                    }
                }
            }
            return view('dashboard');
        } catch (\Throwable $e) {
            Log::info("Failed to retrieve categories from magento");
            Log::info($e);
            return view('dashboard');
            return false;
        }
    }
    
}