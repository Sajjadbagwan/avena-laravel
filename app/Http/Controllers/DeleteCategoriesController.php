<?php
namespace App\Http\Controllers;


class DeleteCategoriesController extends Controller
{
    public function deleteCategories()
    {
        $this->service = configMagento();
        $this->service->init();
        try {
            $result = $this->service->call("categories?searchCriteria=100000");
            print_r($result);

            if(!empty($result)) {
                foreach($result->children_data as $k => $v){
                    if($v->id==2){
                        foreach($v->children_data as $k => $v){
                            try{
                                $id = $v->id;
                                $result = $this->service->call('categories/'.$id, '', 'DELETE');
                            } catch (\Throwable $e) {
                                echo $e;
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            echo $e;
        }
    }  
}
