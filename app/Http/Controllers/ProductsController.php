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
        $file = public_path('file/productFile.csv');
        $productCsvArr = $this->rowDataToCsvController->csvToArray($file);
        $this->magentoAttributes = $this->getAttributeOptionsFromMagento($productCsvArr);
        $this->magentoAttributesCode = $this->getAttributeFromMagento();
        

        $MagentoProducts = array();
        /*$MagentoProducts = $this->getAllProductsFromMagento();*/
        $MagentoProducts = array('2340'=>'1155','2341'=>'1160','2324'=>'1155-2157','2325'=>'1160-2162','2326'=>'1160-10411','2327'=>'1160-5528','2328'=>'1160-20607');

        for ($i = 0; $i < count($productCsvArr); $i ++)
        {
            if($productCsvArr[$i]['productType']=='config'){
                $productSku = $productCsvArr[$i]['AssociateID'].'-'.$productCsvArr[$i]['tbl_derivative_DerivativeID'];
            }else{
                $productSku = $productCsvArr[$i]['AssociateID'];
            }
            if (in_array($productSku, $MagentoProducts)){
                echo "Update process for content id : ".$productCsvArr[$i]['tbl_derivative_DerivativeID'];
                echo '<br>';
                $magento_id = array_search($productSku, $MagentoProducts);
                $this->updateProductInMagento($magento_id,$productCsvArr[$i],$productSku);
            }else{
                echo "Insert process for content id : ".$productCsvArr[$i]['tbl_derivative_DerivativeID'];
                echo '<br>';
                $result = $this->insertProductInMagento($productCsvArr[$i]);
                if(!empty($result)){
                    $MagentoProducts[$result['magento_id']] = $result['magento_sku'];
                }
            }
        }

        $this->addConfigurableMainProduct($productCsvArr);
        echo 'All the products has been updated.';
        die();
    }

    function addConfigurableMainProduct($productCsvArr){
        $MagentoProducts = array();
        $MagentoProducts = $this->getAllProductsFromMagento();
        $mainProductArr = array();
        for ($i = 0; $i < count($productCsvArr); $i ++){
            if($productCsvArr[$i]['productType']=='config'){
                $productSku = $productCsvArr[$i]['AssociateID'].'-'.$productCsvArr[$i]['tbl_derivative_DerivativeID'];
                $key = array_search ($productSku, $MagentoProducts);
                $conPro='';
                if(!empty($mainProductArr[$productCsvArr[$i]['AssociateID']]['configProductId'])){
                    $conPro=$mainProductArr[$productCsvArr[$i]['AssociateID']]['configProductId'].'|'.$key;
                }else{
                    $conPro=$key;
                }
                if($productCsvArr[$i]['productType']=='config'){
                    $mainProductArr[$productCsvArr[$i]['AssociateID']] = $productCsvArr[$i];
                }
                $mainProductArr[$productCsvArr[$i]['AssociateID']]['configProductId']=$conPro;
                $mainProductArr[$productCsvArr[$i]['AssociateID']]['productType']='configurable';
                $mainProductArr[$productCsvArr[$i]['AssociateID']]['configProductOpt'][]=$productCsvArr[$i]['tbl_Product_Derivative1'];
                $mainProductArr[$productCsvArr[$i]['AssociateID']]['configProductOpt'][]=$productCsvArr[$i]['tbl_Product_Derivative2'];
            }     
        }

        foreach($mainProductArr as $k => $productArr){
            $productSku = $productArr['AssociateID'];
            if (in_array($productSku, $MagentoProducts)){
                echo "Update process for content id : ".$productArr['ContentID'];
                echo '<br>';
                $magento_id = array_search($productSku, $MagentoProducts);
                $this->updateProductInMagento($magento_id,$productArr,$productSku);
            }else{
                echo "Insert process for content id : ".$productArr['ContentID'];
                echo '<br>';
                $result = $this->insertProductInMagento($productArr);
                if(!empty($result)){
                    $MagentoProducts[$result['magento_id']] = $result['magento_sku'];
                }
            }
        }
    }

    function insertProductInMagento($csvCatData){
        $resultLogId = '';
        $resultLogId = $this->generateLog($csvCatData['ContentID'],'product');
        $data = $this->getProductData($csvCatData,'insert');
        $result = array();
        $this->service = configMagento();
        $this->service->init();

        try {
            $result = $this->service->call('products', $data, 'POST');
            echo '---------<br/>';
        } catch (\Throwable $e) {
            echo 'Failed to insert product to magento for content ID '.$csvCatData['ContentID'];
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

    function updateProductInMagento($magento_id,$csvCatData,$productSku){
        $resultLogId = '';
        $resultLogId = $this->generateLog($csvCatData['ContentID'],'product');
        $data = $this->getProductData($csvCatData,'update');

        $result = array();
        $this->service = configMagento();
        $this->service->init();
        try {
            $restUrl = 'products/'.$productSku;
            $result = $this->service->call($restUrl, $data, 'PUT');
            echo '---------<br/>';
        } catch (\Throwable $e) {
            $proceesMsg = 'Failed to update product to magento for content ID '.$csvCatData['ContentID'];
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
        $this->updateProductTbl($magento_id,$dataCatTblArr);
        $this->updateGeneratedLog($logIdArr);
    }

    public function getProductData($csvCatData,$curProcess){

        /*echo '<pre>';
        print_r($csvCatData);
        die();*/

        $parentContentId = $csvCatData['ParentContentID'];
        $magentoParentId = $this->findMagentoParentId($parentContentId);

        $category_url = $csvCatData['FileName'];
        $urlArr = array_filter(explode('/', $category_url));
        $currentCatUrl = current($urlArr);
        $extension_attributes = (object)array();
        $typeId = 'simple';

        $magentoCategoryIdsArr = $this->getCategoryMagentoIdfromCategoryContentId($csvCatData['category_content_id']);

        if($csvCatData['productType']=='config'){
            $prefix='tbl_derivative_';
            $sku=$csvCatData['AssociateID'].'-'.$csvCatData[$prefix.'DerivativeID'];
        }else{
            $prefix='tbl_Product_';
            $sku=$csvCatData['AssociateID'];
            $custom_attributes[] = (object)array("attribute_code" => 'special_price', "value" => $csvCatData['tbl_Product_SalePrice']);
        }

        if($csvCatData['productType']=='configurable'){
            $configurableProductOptionsAr = $this->getAllConfigOptionIds($csvCatData['configProductOpt']);
            if(!empty($configurableProductOptionsAr)){
                $extension_attributes->configurable_product_options = $configurableProductOptionsAr;
            }
            if(!empty($csvCatData['configProductId'])){
                $extension_attributes->configurable_product_links = explode("|",$csvCatData['configProductId']);
            }
            $typeId = 'configurable';
        }else{
            if($csvCatData['tbl_Product_Derivative1']!='' && $csvCatData['tbl_Product_Derivative1']!='n/a' && $csvCatData['tbl_derivative_Title']!='' && $csvCatData['tbl_derivative_Title']!='n/a'){
                $val1 = $this->magentoAttributes[$csvCatData['tbl_Product_Derivative1']][$csvCatData['tbl_derivative_Title']];
                $custom_attributes[] = (object)array("attribute_code" => $csvCatData['tbl_Product_Derivative1'], "value" => $val1);
            }
            if($csvCatData['tbl_Product_Derivative2']!='' && $csvCatData['tbl_Product_Derivative2']!='n/a' && $csvCatData['tbl_derivative_Title2']!='' && $csvCatData['tbl_derivative_Title2']!='n/a'){
                $val2 = $this->magentoAttributes[$csvCatData['tbl_Product_Derivative2']][$csvCatData['tbl_derivative_Title2']];
                $custom_attributes[] = (object)array("attribute_code" => $csvCatData['tbl_Product_Derivative2'], "value" => $val2);
            }
        }

        $is_active='false'; if($csvCatData['Enabled']==1){ $is_active='true';}
        $custom_attributes[] = (object)array("attribute_code" => 'url_key', "value" => $currentCatUrl.'-'.$sku);
        $custom_attributes[] = (object)array("attribute_code" => 'meta_title', "value" => $csvCatData['AdditionalPageTitle']);
        $custom_attributes[] = (object)array("attribute_code" => 'meta_keywords', "value" => $csvCatData['MetaKeywords']);
        $custom_attributes[] = (object)array("attribute_code" => 'meta_description', "value" => $csvCatData['MetaDescription']);
        if(!empty($magentoCategoryIdsArr)){
            $custom_attributes[] = (object)array("attribute_code" => 'category_ids', "value" => $magentoCategoryIdsArr);
        }

        $productData = (object)array(
            "sku" => $sku,
            "name" => $csvCatData['PageTitle'],
            "price" => $csvCatData[$prefix.'Price'],
            "status" => 1,
            "type_id" => $typeId,
            "attribute_set_id" => 4,
            "custom_attributes" => $custom_attributes,
            "extension_attributes" => $extension_attributes
        );
            
        $magentoData = (object)array('product' => $productData);
        $data = $magentoData;
        /*echo '<pre>'; print_r($data); echo '</pre>';*/
        return $data;
        
    }

    public function getAllConfigOptionIds($configData){
        $configurableProductOptionsAr=array();
        if(!empty($configData)){
            foreach($configData as $k => $v){
                if($v!='' && $v!='n/a'){
                    $configurableProductOptionsAr[] =   (object)array(
                        "attribute_id"=>$this->magentoAttributesCode[$v],
                        "label"=>$v,
                        "position"=>"0",
                        "values"=>array(
                            (object)array(
                                "value_index"=>1
                            )
                        )
                    );
                }
            }
        }
        return $configurableProductOptionsAr;
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

    public function getAttributeFromMagento()
    {
        $tempAr = array();
        try {
            $this->service = configMagento();
            $this->service->init();
            $result = $this->service->call("products/attributes?searchCriteria=100");
            $temp=array();
            if(!empty($result->items)){
                foreach($result->items as $key => $value){
                    $tempAr[$value->attribute_code] = $value->attribute_id;
                }  
            }
        } catch (\Throwable $e) {
            Log::channel('dailyproducts')->info("The attribute with a attributeCode doesn't exist. Verify the attribute and try again.");
            Log::channel('dailyproducts')->info($e);
        }
        return $tempAr;
    }

    public function getAttributeOptionsFromMagento($productCsvArr){
        $attributesNames=array();
        $productAttributeValue=array();

        foreach($productCsvArr as $key => $value){
            $der1 = $value['tbl_Product_Derivative1'];
            $der2 = $value['tbl_Product_Derivative2'];
            $derTitle1 = $value['tbl_derivative_Title'];
            $derTitle2 = $value['tbl_derivative_Title2'];

            if($der1!='' && $der1!='n/a'){ 
                $attributesNames[] = $der1; 
                if($derTitle1!='' && $derTitle1!='n/a'){
                    $productAttributeValue[$der1][] = $derTitle1;
                }
            }
            if($der2!='' && $der2!='n/a'){ 
                $attributesNames[] = $der2; 
                if($derTitle2!='' && $derTitle2!='n/a'){
                    $productAttributeValue[$der2][] = $derTitle2;
                }
            }
        }

        $attributesNames = array_unique($attributesNames);

        $magentoAttributes = $this->getAllAttributesFromMagento($attributesNames);


        foreach($productAttributeValue as $attributeSet => $attribute){
            foreach($attribute as $key => $attributeLabel){

                if(!array_key_exists($attributeLabel, $magentoAttributes[$attributeSet])){
                    $this->addAttributeOptionsToMagento($attributeSet,$attributeLabel);
                }
            }
        }

        $magentoAttributes = $this->getAllAttributesFromMagento($attributesNames);

        return $magentoAttributes;

    }

    public function getAllAttributesFromMagento($attributesNames){

        $magentoAttributes = array();
        foreach($attributesNames as $key => $attribute){
            $this->service = configMagento();
            $this->service->init();
            $result = $this->service->call("products/attributes/{$attribute}/options");
            $tempAr = array();
            if(!empty($result)) {
                foreach ($result as $rKey => $rValue) {
                    if(trim($rValue->label) != '' && trim($rValue->value) != '') {
                        $tempAr[$rValue->label] = $rValue->value;
                    }
                }
            }
            $magentoAttributes[$attribute] = $tempAr;
        }
        return $magentoAttributes;
    }

    public function addAttributeOptionsToMagento($attribute,$attributeLabel)
    {
        if(trim($attribute) != '' && trim($attributeLabel) != '')
        {
            $this->service = configMagento();
            $this->service->init();
            $data = (object) array(
                "option" => (object) array(
                    "label" => $attributeLabel,
                    "value" => $attributeLabel
                )
            );
            $result = $this->service->call("products/attributes/{$attribute}/options", $data, 'POST');
            /*$this->getAttributeOptionsFromMagento($attribute);*/
            return true;
        } else {
            return false;
        }
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
