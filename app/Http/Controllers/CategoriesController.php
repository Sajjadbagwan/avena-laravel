<?php
namespace App\Http\Controllers;
use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;
use DB;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;


class CategoriesController extends Controller
{
    public function __construct()
    {
        $this->productController = New ProductController;
    }

    public function syncCategory()
    {
        

        $MagentoCategories = array();
        $MagentoCategories = $this->getAllCategoryFromMagento();

        $file = public_path('file/categoryFile.csv');
        $catCsvArr = $this->productController->csvToArray($file);

        for ($i = 0; $i < count($catCsvArr); $i ++)
        {
            $category_url = $catCsvArr[$i]['FileName'];
            $urlArr = array_filter(explode('/', $category_url));
            $currentCatUrl = end($urlArr);
            if (in_array($currentCatUrl, $MagentoCategories)){
                echo "update";
                echo '<br>';
            }else{
                $this->insertCategoryInMagento($catCsvArr[$i],$currentCatUrl);
            }
            
        }
        /*echo '<pre>'; print_r($catCsvArr[$i]); echo '</pre>'; */
        die();

    }

    function insertCategoryInMagento($csvCatData,$currentCatUrl){
        echo '<pre>'; print_r($csvCatData); echo '</pre>';

            $this->service = configMagento();
            $this->service->init();

            $newDate = date("Y-m-d", strtotime($csvCatData['CreatedDate'])).'00:00:00';
            $is_active='false'; if($csvCatData['Enabled']==1){ $is_active='true';}
            $custom_attributes[] = (object)array(
                "attribute_code" => 'url_key',
                "value" => $currentCatUrl
            );

            $categoryData = (object)array(
                "name" => $csvCatData['PageTitle'],
                "is_active" => $is_active,
                "custom_attributes" => $custom_attributes
            );


            $magentoData = (object)array('category' => $categoryData);
            //$data = json_decode(json_encode($magentoData));
            $data = $magentoData;

        $result = $this->service->call('categories', $data, 'POST');
        echo '<pre>'; print_r($result); echo '</pre>';


        die();


    }

    public function insert() {

        /*$this->insert(); die();*/
        $data_category_id = 2;
        $magento_category_id = 2;
        $status = 'success';
        $updated_at = '2024-04-04 10:27:17.000000';
        $created_at = '2024-04-04 10:27:17.000000';
        $flag = 0;

        $values = array('data_category_id' => $data_category_id,
                        'magento_category_id' => $magento_category_id,
                        'status' => $status,
                        'updated_at' => $updated_at,
                        'created_at' => $created_at,
                        'flag' => $flag
                    );
        DB::table('category')->insert($values);

    }

    public function getAllCategoryFromMagento(){
        $this->service = configMagento();
        $this->service->init();
        try {
            $result = $this->service->call("categories/list?searchCriteria");
            $magentoData = array();

            if(isset($result->total_count) && $result->total_count > 0) {
                $removeValuesAr = array("root catalog", "default category");
                $removeValuesIdsAr = array();
                foreach ($result->items as $iKey => $itemAr) {
                    if(!in_array(strtolower($itemAr->name), $removeValuesAr)) {
                        $url_key = '';
                        foreach($itemAr->custom_attributes as $key => $value){
                            if($value->attribute_code == 'url_key'){
                                $url_key = $value->value;
                            }
                        }
                        $magentoData[$itemAr->id] = $url_key;
                    } else {
                        $removeValuesIdsAr[] = $itemAr->id;
                    }
                }
            }
            return $magentoData;
        } catch (\Throwable $e) {
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
