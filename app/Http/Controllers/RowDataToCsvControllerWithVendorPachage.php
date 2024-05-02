<?php
namespace App\Http\Controllers;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Writer\Csv;

class RowDataToCsvController extends Controller
{
    public function __construct()
    {
    }

    function createCSVFromXLS()
    {       
            $reader = new Xls();

            /*$spreadsheet = $reader->load(public_path('file\a.xls'));
            $writer = new Csv($spreadsheet);
            $writer->setDelimiter(',');
            $writer->setEnclosure('"');
            $writer->setLineEnding("\r\n");
            $writer->setSheetIndex(0);
            $writer->save(public_path('file\xyz.csv'));*/

            $main = array();
            $spreadsheet = $reader->load(public_path('file\a.xls'));
            $worksheet = $spreadsheet->getActiveSheet();
            foreach ($worksheet->getRowIterator() as $row) {
                $sub = array();
 
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(FALSE);
                foreach ($cellIterator as $cell) {
                    $sub[] = $cell->getValue();
                }
                array_push($main, $sub);
            }
            print_r($main);


            $fp = fopen(public_path('file/testAvena1.csv'), 'w'); 
            foreach ($main as $fields) { 
                fputcsv($fp, $fields); 
            }  
            fclose($fp); 
            echo 'aaaa';
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
            while (($row = fgetcsv($handle, 100000000, $delimiter)) !== false)
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

    function csvToArrayHeader($filename = '', $delimiter = ',')
    {
        if (!file_exists($filename) || !is_readable($filename))
            return false;

        $header = null;
        $headerCount = 0;
        $data = array();
        if (($handle = fopen($filename, 'r')) !== false)
        {
            while (($row = fgetcsv($handle, 10000000, $delimiter)) !== false)
            {   
                if (!$header){
                    $header = $row;
                    fclose($handle);
                    return $header;
                }
            }
        }
    }

    function createCsvFromData(){
        $this->createCSVFromXLS();
        die();



        $file1 = public_path('file/Avena1.csv'); /* Content sheet xls to csv */
        $file2 = public_path('file/Avena2.csv'); /* Derivative product sheet xls to cvs */
        $file3 = public_path('file/Avena3.csv'); /* Product sheet xls to csv */
        $file4 = public_path('file/Avena4.csv'); /* Category sheet xls to csv */
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
                $productsArrAll[$csvDataArray[$i]['AssociateID']]['category_content_id'] = '';
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
            $productsArrAll[$productArr2[$i]['ProductID']]['config'][] = $productArr2[$i];         
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

                if(!empty($categoryArr4[$i]['CategoryContentID'])){
                    $productsArrAll[$productidContentidMap[$categoryArr4[$i]['ProductContentID']]]['category_content_id'] =  $categoryArr4[$i]['CategoryContentID'];
                }
                
            }
            
        }
        
        $cnt=0;
        $final_product=array();
        foreach($productsArrAll as $proConId => $prodArr){
            $configtempArr = array();
            $configtempArr = $prodArr["config"];

            unset($prodArr["config"]);
            $header = $this->csvToArrayHeader($file2);

            if(($prodArr['tbl_Product_Derivative1']=='n/a' || $prodArr['tbl_Product_Derivative1']=='') && ($prodArr['tbl_Product_Derivative2']=='n/a' || $prodArr['tbl_Product_Derivative2']=='')){
                $type = 'simple';
            }else{
                $type = 'config';
            }
            
            if(count($configtempArr)>0){
                foreach ($configtempArr as $key => $derProds) {
                    $final_product[$cnt] = $prodArr;
                    $final_product[$cnt]['productType'] = $type;
                    foreach ($derProds as $key => $value) {
                        $final_product[$cnt]['tbl_derivative_'.$key] = $value;
                    }
                    $cnt++;
                }
            }else{

                $final_product[$cnt] = $prodArr;
                $final_product[$cnt]['productType'] = $type;
                foreach($header as $key => $value){
                   $final_product[$cnt]['tbl_derivative_'.$value]='';
                }
                $cnt++;
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
        foreach ($final_product as $line) {
            if($flag==0){ fputcsv($file, array_keys($line)); }
            fputcsv($file, $line);
            $flag=1;
        }
        fclose($file);
        echo 'done';
    }

}

