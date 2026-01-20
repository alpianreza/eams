<?php

namespace App\Models;

use CodeIgniter\Model;

class ComplianceChecklistLogItemModel extends Model
{
protected $table = 'compliance_checklist_log_items';
protected $primaryKey = 'id';

protected $allowedFields = [
'checklist_log_id',
'checklist_item_id',
'status',
'remark'
];
}