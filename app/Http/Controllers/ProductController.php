<?php
namespace App\Http\Controllers;
use App\Http\Controllers\ItemController;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->itemController = New ItemController;
    }
    public function index()
    {
        
        $this->service = configMagento();

        $file = public_path('file/test.csv');

        $productArr = $this->csvToArray($file);

        for ($i = 0; $i < count($productArr); $i ++)
        {
            $sku = $productArr[$i]['sku'].'1';
            $name = $productArr[$i]['name'];
            $attribute_set_id = $productArr[$i]['attribute_set_id'];
            $price = $productArr[$i]['price'];
            $status = $productArr[$i]['status'];
            $visibility = $productArr[$i]['visibility'];
            $type_id = $productArr[$i]['type_id'];

            $extension_attributes = (object)array();
            $extension_attributes->website_ids = array(1);
            $extension_attributes->stock_item['qty'] = $productArr[$i]['qty'];
            $extension_attributes->stock_item['is_in_stock'] = $productArr[$i]['is_in_stock'];
            $extension_attributes->stock_item['stock_id'] = $productArr[$i]['stock_id'];

            $custom_attributes[] = (object)array(
                "attribute_code" => 'category_ids',
                "value" => $productArr[$i]['category_ids']
            );

            $productData = (object)array(
                "sku" => $sku,
                "name" => $name,
                "attribute_set_id" => $attribute_set_id,
                "price" => $price,
                "status" => $status,
                "visibility" => $visibility,
                "type_id" => $type_id,
                "extension_attributes" => $extension_attributes,
                "custom_attributes" => $custom_attributes
            );

            $magentoData = (object)array('product' => $productData);
            //$data = json_decode(json_encode($magentoData));
            $data = $magentoData;
            
            echo '<pre>';
            print_r($data);
            echo '</pre>';
            $result = $this->service->call('products', $data, 'POST');
        }
   
             
        echo '<pre>';
        print_r($result);
        echo '</pre>';
        die();   

    }

    function csvToArray($filename = '', $delimiter = ',')
    {
        if (!file_exists($filename) || !is_readable($filename))
            return false;

        $header = null;
        $headerCount = 0;
        $data = array();
        if (($handle = fopen($filename, 'r')) !== false)
        {
            while (($row = fgetcsv($handle, 1000, $delimiter)) !== false)
            {
                if (!$header){
                    $header = $row;
                }else{
                    $headerCount = count($header);
                    $row = array_pad($row, $headerCount,'');
                    $data[] = array_combine($header, $row);
                }
            }
            fclose($handle);
        }
        return $data;
    }

    function test(){

        $file1 = public_path('file/Avena1.csv');
        
    }

    function testdata(){

        $file1 = public_path('file/Avena1.csv');
        $file2 = public_path('file/Avena2.csv');
        $file3 = public_path('file/Avena3.csv');
        $file4 = public_path('file/Avena4.csv');
        $productsArrAll = array();
        $categoryContentArray = array();

        $productsArrAll2 = array();
        $productsArrAll3 = array();
        $CategoryArrAll4 = array();

        $ArrayOfRedirection = array();
        $ArrayOfCms = array();

        $productidContentidMap = array();

        /* Product and category sepration from product data */
        $csvDataArray = $this->csvToArray($file1);
        for ($i = 0; $i < count($csvDataArray); $i ++)
        {
            if($csvDataArray[$i]['AssociateType']=='Product'){
                $productsArrAll[$csvDataArray[$i]['AssociateID']] = $csvDataArray[$i];
                $productidContentidMap[$csvDataArray[$i]['ContentID']] = $csvDataArray[$i]['AssociateID'];

            }elseif($csvDataArray[$i]['AssociateType']=='Category'){
                $categoryContentArray[$csvDataArray[$i]['ContentID']] = $csvDataArray[$i];
            }elseif($csvDataArray[$i]['AssociateType']=='301Redirect'){
                $ArrayOfRedirection[$csvDataArray[$i]['ContentID']] = $csvDataArray[$i];
            }else{
                $ArrayOfCms[$csvDataArray[$i]['ContentID']] = $csvDataArray[$i];
            }
        }
        /* Product and category sepration from product data end */

        /* catagory sepration and parent added */
        $catagoryUrlContentId = array();
        foreach ($categoryContentArray as $key => $value) {
            $catagoryUrlContentId[$value['FileName']] = $value['ContentID'];
        }

        $catagoryTreeCounter = 0;
        foreach ($categoryContentArray as $key => $value) {
            $urlArray = array();
            $urlArray = array_values(array_filter(explode('/', $value['FileName'])));
            $categoryContentParent='';
            $link = '/';
            for ($i = 0; $i < count($urlArray)-1; $i++){
                $link = $link.$urlArray[$i].'/';
                $categoryContentParent = $categoryContentParent.'|'.$catagoryUrlContentId[$link];
            }
            $categoryContentArray[$value['ContentID']]['paraent_catagory_content_id'] = ltrim($categoryContentParent,'|') ;;
        }
        /* catagory sepration end */

        /* added sheet 2 in product array */
        $productArr2 = $this->csvToArray($file2);
        for ($i = 0; $i < count($productArr2); $i ++)
        {
            foreach ($productArr2[$i] as $key => $value) {
                $productsArrAll[$productArr2[$i]['ProductID']]['tbl_Derivative_'.$key] = $value;
            }            
        }
        /* added sheet 2 in product array end */

        /* added sheet 3 in product array */
        $productArr3 = $this->csvToArray($file3);
        for ($i = 0; $i < count($productArr3); $i ++)
        {
            foreach ($productArr3[$i] as $key => $value) {
                $productsArrAll[$productArr3[$i]['ProductID']]['tbl_Product_'.$key] = $value;
            }            
        }
        /* added sheet 3 in product array end */

        $categoryArr4 = $this->csvToArray($file4);
        for ($i = 0; $i < count($categoryArr4); $i ++)
        {
            $checkExisting = $productsArrAll[$productidContentidMap[$categoryArr4[$i]['ProductContentID']]];
            if(isset($checkExisting['category_content_id']) && !empty($checkExisting['category_content_id'])){
                $productsArrAll[$productidContentidMap[$categoryArr4[$i]['ProductContentID']]]['category_content_id'] =  $checkExisting['category_content_id'].'|'.$categoryArr4[$i]['CategoryContentID'];
            }else{
                $productsArrAll[$productidContentidMap[$categoryArr4[$i]['ProductContentID']]]['category_content_id'] =  $categoryArr4[$i]['CategoryContentID'];
            }
            
        }

        $fileName = public_path('file/redirectFile.csv');
        $file = fopen($fileName, 'w');
        $flag=0;
        foreach ($ArrayOfRedirection as $line) {
            if($flag==0){ fputcsv($file, array_keys($line)); }
            fputcsv($file, $line);
            $flag=1;
        }
        fclose($file);

        $fileName = public_path('file/cmsFile.csv');
        $file = fopen($fileName, 'w');
        $flag=0;
        foreach ($ArrayOfCms as $line) {
            if($flag==0){ fputcsv($file, array_keys($line)); }
            fputcsv($file, $line);
            $flag=1;
        }
        fclose($file);

        $fileName = public_path('file/categoryFile.csv');
        $file = fopen($fileName, 'w');
        $flag=0;
        foreach ($categoryContentArray as $line) {
            if($flag==0){ fputcsv($file, array_keys($line)); }
            fputcsv($file, $line);
            $flag=1;
        }
        fclose($file);

        $fileName = public_path('file/productFile.csv');
        $file = fopen($fileName, 'w');
        $flag=0;
        foreach ($productsArrAll as $line) {
            if($flag==0){ fputcsv($file, array_keys($line)); }
            fputcsv($file, $line);
            $flag=1;
        }
        fclose($file);
        echo 'done';

    }

}

