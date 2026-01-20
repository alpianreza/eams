<?php

namespace App\Controllers;

use App\Models\AssetItemTypeModel;

class AssetItemTypeController extends BaseController
{
  public function byCategory($categoryId)
  {
    $model = new AssetItemTypeModel();
    return $this->response->setJSON(
      $model->where('inventory_category_id', $categoryId)
        ->where('active', 1)
        ->findAll()
    );
  }
}
