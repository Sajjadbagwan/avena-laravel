<?php
namespace App\Http\Controllers;
use App\Http\Controllers\RowDataToCsvController;
use Illuminate\Http\Request;
use DB;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;


class CategoriesImageController extends Controller
{
    public function __construct()
    {
        $this->rowDataToCsvController = New RowDataToCsvController;
    }

    public function syncCategoryImage()
    {
        $file = public_path('file/categoryFile.csv');
        $catCsvArr = $this->rowDataToCsvController->csvToArray($file);

        /* Generate image */
        $this->moveCategoryImagesToParentFolder($catCsvArr);
        
    }

    function moveCategoryImagesToParentFolder($catCsvArr)
    {
        for ($i = 0; $i < count($catCsvArr); $i ++)
        {
            $contentId = $catCsvArr[$i]['ContentID'];
            $msg = '';
            $content='';
            $path = public_path('file/images/'.$contentId);
            $imageArr = array();
            $imageContent = array();

            if(is_dir($path)){
                if ($handle = opendir($path)) {

                    while (false !== ($entry = readdir($handle))) {
                        if($entry!='.' && $entry!='..'){
                            /*echo $entry.'/n';*/
                            $ext = pathinfo($entry, PATHINFO_EXTENSION);
                            if($ext=='jpeg' || $ext=='jpg' || $ext=='png'){
                                $pathFile    = $path.'/'.$entry;
                                $img = array();
                                $img = getimagesize($pathFile);
                                $imageArr[$pathFile] = $img[0];
                            }
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
                $source = $path;
                $destination = public_path('file/images/parent/'.$name);
 
                if( !copy($source, $destination) ) {  
                    echo "File can't be copied! \n";  
                }  
                else {  
                    echo "File has been copied! \n";  
                }
            }
        }

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
