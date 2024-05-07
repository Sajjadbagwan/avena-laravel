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
        /*$file = public_path('file/productFileMainTest.csv');*/
        $file = public_path('file/productFile.csv');
        $productCsvArr = $this->rowDataToCsvController->csvToArray($file);
        

        $this->magentoAttributes = $this->getAttributeOptionsFromMagento($productCsvArr);
        $this->magentoAttributesCode = $this->getAttributeFromMagento();        

        $MagentoProducts = array();
        $MagentoProducts = $this->getAllProductsFromMagento();

        $result = array();
        for ($i = 0; $i < count($productCsvArr); $i ++)
        {
            if($productCsvArr[$i]['productType']=='config'){
                $productSku = $productCsvArr[$i]['AssociateID'].'-'.$productCsvArr[$i]['tbl_derivative_DerivativeID'];
            }else{
                $productSku = $productCsvArr[$i]['AssociateID'];
            }
            if (in_array($productSku, $MagentoProducts)){
                echo "Update process for content id : ".$productCsvArr[$i]['ContentID'].'-'.$productCsvArr[$i]['tbl_derivative_DerivativeID'];
                echo '<br>';
                $magento_id = array_search($productSku, $MagentoProducts);
                $this->updateProductInMagento($magento_id,$productCsvArr[$i],$productSku);
            }else{
                echo "Insert process for content id : ".$productCsvArr[$i]['ContentID'].'-'.$productCsvArr[$i]['tbl_derivative_DerivativeID'];
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
                $key='';
                if(!empty($MagentoProducts)){
                    $key = array_search ($productSku, $MagentoProducts);
                }
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
                $mainProductArr[$productCsvArr[$i]['AssociateID']]['configProductOpt'][]=strtolower($productCsvArr[$i]['tbl_Product_Derivative1']);
                $mainProductArr[$productCsvArr[$i]['AssociateID']]['configProductOpt'][]=strtolower($productCsvArr[$i]['tbl_Product_Derivative2']);
            }     
        }
        foreach($mainProductArr as $k => $productArr){
            $productSku = $productArr['AssociateID'];
            if (in_array($productSku, $MagentoProducts)){
                echo "Update process for content id : ".$productArr['ContentID'].'-'.$productArr['AssociateID'];
                echo '<br>';
                $magento_id = array_search($productSku, $MagentoProducts);
                $this->updateProductInMagento($magento_id,$productArr,$productSku);
            }else{
                echo "Insert process for content id : ".$productArr['ContentID'].'-'.$productArr['AssociateID'];
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
        if($csvCatData['productType']=='config'){ 
            $updateId=$csvCatData['ContentID'].'-'.$csvCatData['tbl_derivative_DerivativeID'];
        }else{
            $updateId=$csvCatData['ContentID'];
        }
        $resultLogId = $this->generateLog($updateId,'product');
        $data = $this->getProductData($csvCatData,'insert');
        $result = array();
        $this->service = configMagento();
        $this->service->init();
        $errorMsg= '';

        

        try {
            $result = $this->service->call('products', $data, 'POST');
            echo '---------<br/>';
        } catch (\Throwable $e) {
            $errorMsg = $e;
            echo 'Failed to insert product to magento for content ID '.$csvCatData['ContentID'];
            echo '<br/>';
            Log::info("Failed to insert category to magento");
            Log::info($e);
        }

        /*echo '<pre>'; print_r($data); die();*/

        $msg='';
        if(!empty($result)){
            $sku = $result->sku;
            $msg = $this->addMediaToMagentoProduct($sku,$csvCatData);
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
            $logIdArr['message'] = $msg.$errorMsg;
        }
        $this->insertProductTbl($dataCatTblArr);
        $this->updateGeneratedLog($logIdArr);
        return $returnArr;
    }

    function updateProductInMagento($magento_id,$csvCatData,$productSku){
        $resultLogId = '';
        if($csvCatData['productType']=='config'){ 
            $updateId=$csvCatData['ContentID'].'-'.$csvCatData['tbl_derivative_DerivativeID'];
        }else{
            $updateId=$csvCatData['ContentID'];
        }
        $resultLogId = $this->generateLog($updateId,'product');
        $data = $this->getProductData($csvCatData,'update');

        $result = array();
        $this->service = configMagento();
        $this->service->init();
        $errorMsg= '';

        

        try {
            $restUrl = 'products/'.$productSku;
            $result = $this->service->call($restUrl, $data, 'PUT');
            echo '---------<br/>';
        } catch (\Throwable $e) {
            $errorMsg = $e;
            $proceesMsg = 'Failed to update product to magento for content ID '.$csvCatData['ContentID'];
            echo $proceesMsg;
        }

        /*echo '<pre>'; print_r($data); die();*/

        $msg = '';
        if(!empty($result)){
            $sku = $result->sku;
            $msg = $this->addMediaToMagentoProduct($sku,$csvCatData);
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
            $logIdArr['message'] = $msg.$errorMsg;
        }
        $this->updateProductTbl($magento_id,$dataCatTblArr);
        $this->updateGeneratedLog($logIdArr);
    }

    public function getProductData($csvCatData,$curProcess){

        $parentContentId = $csvCatData['ParentContentID'];
        $magentoParentId = $this->findMagentoParentId($parentContentId);

        $category_url = $csvCatData['FileName'];
        $urlArr = array_filter(explode('/', $category_url));
        $currentCatUrl = current($urlArr);
        $extension_attributes = (object)array();
        $typeId = 'simple';
        $status=0;

        $magentoCategoryIdsArr = $this->getCategoryMagentoIdfromCategoryContentId($csvCatData['category_content_id']);

        if($csvCatData['productType']=='config'){
            $prefix='tbl_derivative_';
            $sku=$csvCatData['AssociateID'].'-'.$csvCatData[$prefix.'DerivativeID'];
            $custom_attributes[] = (object)array("attribute_code" => 'gtin', "value" => $csvCatData[$prefix.'DerivGTIN']);
            $custom_attributes[] = (object)array("attribute_code" => 'mpn', "value" => $csvCatData[$prefix.'DerivMPN']);
            $custom_attributes[] = (object)array("attribute_code" => 'rank', "value" => $csvCatData[$prefix.'DerivRank']);
            $status = $csvCatData[$prefix.'Enabled'];
            $visibility=1;
        }else{
            $prefix='tbl_Product_';
            $sku=$csvCatData['AssociateID'];
            $custom_attributes[] = (object)array("attribute_code" => 'special_price', "value" => $csvCatData['tbl_Product_SalePrice']);
            $custom_attributes[] = (object)array("attribute_code" => 'ts_dimensions_height', "value" => $csvCatData['tbl_Product_Height']);
            $custom_attributes[] = (object)array("attribute_code" => 'ts_dimensions_width', "value" => $csvCatData['tbl_Product_Width']);
            $custom_attributes[] = (object)array("attribute_code" => 'gtin', "value" => $csvCatData[$prefix.'GTIN']);
            $custom_attributes[] = (object)array("attribute_code" => 'mpn', "value" => $csvCatData[$prefix.'MPN']);
            $custom_attributes[] = (object)array("attribute_code" => 'rank', "value" => $csvCatData['Rank']);
            $custom_attributes[] = (object)array("attribute_code" => 'productrank', "value" => $csvCatData['ProductRank']);
            $custom_attributes[] = (object)array("attribute_code" => 'mapping', "value" => $csvCatData['MapID']);
            $status = $csvCatData['Enabled'];
            $visibility=4;
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
                $val1 = $this->magentoAttributes[strtolower($csvCatData['tbl_Product_Derivative1'])][$csvCatData['tbl_derivative_Title']];
                $custom_attributes[] = (object)array("attribute_code" => strtolower($csvCatData['tbl_Product_Derivative1']), "value" => $val1);
            }
            if($csvCatData['tbl_Product_Derivative2']!='' && $csvCatData['tbl_Product_Derivative2']!='n/a' && $csvCatData['tbl_derivative_Title2']!='' && $csvCatData['tbl_derivative_Title2']!='n/a'){
                $val2 = $this->magentoAttributes[strtolower($csvCatData['tbl_Product_Derivative2'])][$csvCatData['tbl_derivative_Title2']];
                $custom_attributes[] = (object)array("attribute_code" => strtolower($csvCatData['tbl_Product_Derivative2']), "value" => $val2);
            }
        }

        $is_active='false'; if($csvCatData['Enabled']==1){ $is_active='true';}
        $custom_attributes[] = (object)array("attribute_code" => 'url_key', "value" => $currentCatUrl.'-'.$sku);
        $custom_attributes[] = (object)array("attribute_code" => 'meta_title', "value" => $csvCatData['AdditionalPageTitle']);
        $custom_attributes[] = (object)array("attribute_code" => 'meta_keyword', "value" => $csvCatData['MetaKeywords']);
        $custom_attributes[] = (object)array("attribute_code" => 'meta_description', "value" => $csvCatData['MetaDescription']);


        

        if($csvCatData['productType']=='simple'){
            if(empty($csvCatData[$prefix.'Code'])){
                $custom_attributes[] = (object)array("attribute_code" => 'code', "value" => $csvCatData['tbl_derivative_Code']);
            }else{
                $custom_attributes[] = (object)array("attribute_code" => 'code', "value" => $csvCatData[$prefix.'Code']);
            }

            if(empty($csvCatData[$prefix.'Location'])){
                $custom_attributes[] = (object)array("attribute_code" => 'locationstock', "value" => $csvCatData['tbl_derivative_Location']);
            }else{
                $custom_attributes[] = (object)array("attribute_code" => 'locationstock', "value" => $csvCatData[$prefix.'Location']);
            }

            if(empty($csvCatData[$prefix.'Weight'])){
                $custom_attributes[] = (object)array("attribute_code" => 'weight', "value" => $csvCatData['tbl_derivative_Weight']);
            }else{
                $custom_attributes[] = (object)array("attribute_code" => 'weight', "value" => $csvCatData[$prefix.'Weight']);
            }
        }else{
            $custom_attributes[] = (object)array("attribute_code" => 'weight', "value" => $csvCatData[$prefix.'Weight']);
            $custom_attributes[] = (object)array("attribute_code" => 'locationstock', "value" => $csvCatData[$prefix.'Location']);
            $custom_attributes[] = (object)array("attribute_code" => 'code', "value" => $csvCatData[$prefix.'Code']);
        }


        $custom_attributes[] = (object)array("attribute_code" => 'specialnote', "value" => $csvCatData['tbl_Product_SpecialNote']);
        $custom_attributes[] = (object)array("attribute_code" => 'specialnoteshort', "value" => $csvCatData['tbl_Product_SpecialNoteShort']);



        if(!empty($magentoCategoryIdsArr)){
            $custom_attributes[] = (object)array("attribute_code" => 'category_ids', "value" => $magentoCategoryIdsArr);
        }

        if($csvCatData[$prefix.'TotalStock']=='' || $csvCatData[$prefix.'TotalStock']==' '){
            $qty = 0;
        }else{
            $qty = $csvCatData[$prefix.'TotalStock'];
        }

        /*$extension_attributes->stock_item = (object)array("qty" => $qty);*/

        if($qty>0){
            $extension_attributes->stock_item = (object)array("qty" => $qty,"is_in_stock" => true);
        }else{
            $extension_attributes->stock_item = (object)array("qty" => $qty,"is_in_stock" => false);
        }


        $productData = (object)array(
            "sku" => $sku,
            "name" => $csvCatData['PageTitle'],
            "price" => $csvCatData[$prefix.'Price'],
            "status" => $status,
            "visibility" => $visibility,
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
            $der1 = strtolower($value['tbl_Product_Derivative1']);
            $der2 = strtolower($value['tbl_Product_Derivative2']);
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

    public function getAttributesFromMagento(){
        $allAttr = array();
        $this->service = configMagento();
        $this->service->init();
        try{
            $result = $this->service->call("products/attributes?searchCriteria=100000");
        } catch (\Throwable $e) {
            $errorMsg = $e;
            echo $errorMsg;
        }
        foreach($result->items as $k => $v){
            $allAttr[]=$v->attribute_code;
        }
        return $allAttr;
    }

    public function addAttributeInMagento($attribute){

        $this->service = configMagento();
        $this->service->init();
        $attributeData = (object)array(
            "is_wysiwyg_enabled" => false,
            "is_html_allowed_on_front" => true,
            "used_for_sort_by" => false,
            "is_filterable" => false,
            "is_filterable_in_search" => false,
            "is_used_in_grid" => false,
            "is_visible_in_grid" => true,
            "is_filterable_in_grid" => true,
            "position" => 0,
            "apply_to" => [],
            "is_searchable" => "1",
            "is_visible_in_advanced_search" => "1",
            "is_comparable" => "1",
            "is_used_for_promo_rules" => "0",
            "is_visible_on_front" => "1",
            "used_in_product_listing" => "1",
            "is_visible" => true,
            "scope" => "global",
            "is_required" => false,
            "is_user_defined" => true,
            "default_frontend_label" => strtolower($attribute),
            "frontend_labels" => [],
            "backend_type" => "int",
            "source_model" => "Magento\\Eav\\Model\\Entity\\Attribute\\Source\\Table",
            "default_value" => "",
            "attribute_code" => strtolower($attribute),
            "frontend_input" => "select",
            "default_frontend_label" => strtolower($attribute),
            "backend_type" => "text"
        );  
        $data = (object)array('attribute' => $attributeData);

        try{
            $result = $this->service->call('products/attributes', $data, 'POST');
        } catch (\Throwable $e) {
            $errorMsg = $e;
            echo $errorMsg;
        }
        return;
    }

    public function getAllAttributesFromMagento($attributesNames){

        
        $allAttr = $this->getAttributesFromMagento();

        $magentoAttributes = array();
        foreach($attributesNames as $key => $attribute){
            $attribute = strtolower($attribute);
            $this->service = configMagento();
            $this->service->init();

            if(!in_array($attribute,$allAttr)){
                $this->addAttributeInMagento($attribute);
            }

            try {
                $result = $this->service->call("products/attributes/{$attribute}/options");
            } catch (\Throwable $e) {
                $errorMsg = $e;
                echo $errorMsg;
            }

            /*echo '<pre>'; print_r($result); die();*/

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
            $result = $this->service->call("products?searchCriteria=100000");
            $magentoData = array();
            if(isset($result->total_count) && $result->total_count > 0) {
                foreach ($result->items as $iKey => $itemAr) {
                    $magentoData[$itemAr->id] = $itemAr->sku;
                }
            }
            return $magentoData;
        }catch (\Throwable $e) {
            Log::info("Failed to retrieve products from magento");
            Log::info($e);
            echo $e;
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

    public function addMediaToMagentoProduct($sku,$csvCatData){

        $productImage = $csvCatData['tbl_derivative_ProductImage'];

        echo 'tttttt'.$productImage'tttttt';
        $msg = '';
        if(!empty($productImage)){
            $imageContent = array();
            $arrayImage = array();
            $image = explode(",",$productImage);
            foreach($image as $key => $value){
                $name = $value;
                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $url = 'file/images/'.$csvCatData['ContentID'].'/';
                $path = public_path($url.$value);
                if (@getimagesize($path)) {
                    $type = pathinfo($path, PATHINFO_EXTENSION);
                    $data = file_get_contents($path);
                    $base64 = base64_encode($data);
                    $fileType = $this->getImageType($name);
                    $imageContent[] = (object) array(
                        "media_type" => "image",
                        "label" => $name,
                        "position" => 1,
                        "disabled" => false,
                        "types" => array(
                            "image",
                            "small_image",
                            "thumbnail"
                        ),
                        "file" => $name,
                        "content" => (object) array(
                            "base64_encoded_data" => $base64,
                            "type" => $fileType,
                            "name" => $name
                        )
                    );
                }
            }

            if(!empty($imageContent)){
                $arrayImage = (object) array(
                    "product" => (object) array(
                        "media_gallery_entries" => $imageContent
                    )
                );
                $route = "products/".$sku;
                if(!empty($data)){

                    try{
                        $result = $this->service->call($route, $arrayImage, 'PUT');
                    } catch (\Throwable $e) {
                        $errorMsg = $e;
                        $msg = $errorMsg;
                    }

                }
            }

        }else{
            echo 'hello'; die();
            $msg = $this->addMediaToMagentoByFolder($sku,$csvCatData);
        }
        return $msg;
    }

    public function addMediaToMagentoByFolder($sku,$csvCatData){

        echo 'aaaaa';
        die();

        $contentId = $csvCatData['ContentID'];
        $msg = '';
        $content='';
        $path = public_path('file/images/'.$csvCatData['ContentID']);
        $imageArr = array();
        $imageContent = array();

        if(is_dir($path)){
            if ($handle = opendir($path)) {

                while (false !== ($entry = readdir($handle))) {
                    if($entry!='.' && $entry!='..'){
                        /*echo $entry.'/n';*/
                        $pathFile    = $path.'/'.$entry;
                        $img = array();
                        $img = getimagesize($pathFile);
                        $imageArr[$pathFile] = $img[0];
                    }
                }
                closedir($handle);
            }
        }

        if(!empty($imageArr)){

            $value = max($imageArr);
            $key = array_search($value, $imageArr);
            $path = $key;

            $name = substr($path, strrpos($path, '/') + 1);

            if (@getimagesize($path)) {
                $type = pathinfo($path, PATHINFO_EXTENSION);
                $data = file_get_contents($path);
                $base64 = base64_encode($data);
                $fileType = $this->getImageType($name);
                $imageContent[] = (object) array(
                    "media_type" => "image",
                    "label" => $name,
                    "position" => 1,
                    "disabled" => false,
                    "types" => array(
                        "image",
                        "small_image",
                        "thumbnail"
                    ),
                    "file" => $name,
                    "content" => (object) array(
                        "base64_encoded_data" => $base64,
                        "type" => $fileType,
                        "name" => $name
                    )
                );

            }

            if(!empty($imageContent)){
                $arrayImage = (object) array(
                    "product" => (object) array(
                        "media_gallery_entries" => $imageContent
                    )
                );
                $route = "products/".$sku;
                if(!empty($data)){
                    try{
                        $result = $this->service->call($route, $arrayImage, 'PUT');
                    } catch (\Throwable $e) {
                        $errorMsg = $e;
                        $msg = $errorMsg;
                    }
                }
            }

        }
        return $msg;
    }

    public function getImageType($name){
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $fileType = "";
        switch ($ext) {
            case 'jpg':
                $fileType = "image/jpeg";
                break;
            case 'png':
                $fileType = "image/png";
                break;
            default:
                $fileType = "image/jpeg";
                break;
        }
        return $fileType;
    }
    
}
