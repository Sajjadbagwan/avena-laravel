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
            $sku = $productArr[$i]['sku'];
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
   
        die();        
        echo '<pre>';
        print_r($result);
        echo '</pre>';

    }

    function csvToArray($filename = '', $delimiter = ',')
    {
        if (!file_exists($filename) || !is_readable($filename))
            return false;

        $header = null;
        $data = array();
        if (($handle = fopen($filename, 'r')) !== false)
        {
            while (($row = fgetcsv($handle, 1000, $delimiter)) !== false)
            {
                if (!$header)
                    $header = $row;
                else
                    $data[] = array_combine($header, $row);
            }
            fclose($handle);
        }

        return $data;
    }

    function test(){

        $file1 = public_path('file/Avena1.csv');
        $file2 = public_path('file/Avena2.csv');
        $file3 = public_path('file/Avena3.csv');
        $file4 = public_path('file/Avena4.csv');
        $productArr1 = $this->csvToArray($file1);

        echo '<pre>';
        print_r($productArr1);
        echo '</pre>';
        die();

        for ($i = 0; $i < count($productArr1); $i ++)
        {

        }
    }
    function testdata(){
        echo 'aaa';

        $file1 = public_path('file/Avena1.csv');
        $file2 = public_path('file/Avena2.csv');
        $file3 = public_path('file/Avena3.csv');
        $file4 = public_path('file/Avena4.csv');
        $productArr1 = $this->csvToArray($file1);
        $productsArr = array();
        $categoryArr = array();
        for ($i = 0; $i < count($productArr1); $i ++)
        {
            if($productArr1[$i]['AssociateType']=='Product'){
                $productsArr[$productArr1[$i]['AssociateID']] = $productArr1[$i];
            }
            if($productArr1[$i]['AssociateType']=='Category'){

            }
        }

        $productArr2 = $this->csvToArray($file2);
        for ($i = 0; $i < count($productArr2); $i ++)
        {
            foreach ($productArr2[$i] as $key => $value) {
                $productsArr[$productArr2[$i]['ProductID']]['tbl_Derivative_'.$key] = $value;
            }            
        }
        $productArr3 = $this->csvToArray($file3);
        for ($i = 0; $i < count($productArr3); $i ++)
        {
            foreach ($productArr3[$i] as $key => $value) {
                $productsArr[$productArr3[$i]['ProductID']]['tbl_Product_'.$key] = $value;
            }            
        }


        echo '<pre>';
        print_r($productsArr);
        echo '</pre>';
        die();


        echo '<pre>';
        print_r($productsArr);
        echo '</pre>';
        die();

    }

}
