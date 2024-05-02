<?php
namespace App\Http\Controllers;
use App\Http\Controllers\RowDataToCsvController;
use Illuminate\Http\Request;
use DB;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;


class CmspagesController extends Controller
{
    public function __construct()
    {
        $this->rowDataToCsvController = New RowDataToCsvController;
    }

    public function syncCmspages()
    {
        $file = public_path('file/cmsFile.csv');
        $cmsCsvArr = $this->rowDataToCsvController->csvToArray($file);



        $content='';
        $path = public_path('file\content\test');
        if(is_dir($path)){
            if ($handle = opendir($path)) {
                while (false !== ($entry = readdir($handle))) {
                    echo "$entry\n";
                    if($entry!='.' && $entry!='..'){
                        $pathFile    = public_path('file\content\test\\'.$entry);
                        $fileData = file_get_contents($pathFile, false);
                        if($fileData!='' && !str_contains($fileData, 'error') && !str_contains($fileData, 'Error')){
                            $content = $content.$fileData;
                        }
                    }
                }
                closedir($handle);
            }
        }
        echo $content;
        die();



        for ($i = 0; $i < count($cmsCsvArr); $i ++)
        {
            echo $ContentID = $cmsCsvArr[$i]['ContentID'];
            echo $ContentID = $cmsCsvArr[$i]['ContentID'];
        }
        echo 'All the CMS pages has been updated.';
        die();
    }
}
