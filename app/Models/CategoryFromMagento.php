<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryFromMagento extends Model
{
    use HasFactory;

    protected $table = "category";

    protected $fillable = [
        'data_category_id', 'magento_category_id', 'status', 'updated_at', 'created_at'
    ];

    public function insertUpdateCategory($dataAr = array())
    {
        if(!empty($dataAr) && isset($dataAr['magento_id'])){
            $categoryData = $this->getCategoryByMagentoId($dataAr['magento_id']);
            if(empty($categoryData) && !empty($dataAr)){
                $this->insertCategory($dataAr);
            }
            else if(!empty($categoryData) && !empty($dataAr)) {
                $this->updateCategory($dataAr['magento_id'],$dataAr);
            }
        }
    }

    public function insertCategory($dataAr)
    {
        if(!empty($dataAr)) {
            return $this->create($dataAr);
        } else {
            return false;
        }
    }

    public function updateCategory($magentoId, $dataAr)
    {
        if(is_numeric($magentoId) && !empty($dataAr)) {
            return $this->where('magento_id', $magentoId)->update($dataAr);
        } else {
            return false;
        }
    }

    public function getCategoryByMagentoId($magentoId) {
        $record = self::where('magento_id', $magentoId)->first();
        if($record) {
            return $record;
        } else {
            return array();
        }
    }
}
