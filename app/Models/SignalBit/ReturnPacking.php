<?php

namespace App\Models\SignalBit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnPacking extends Model
{
    use HasFactory;

    protected $connection = 'mysql_sb';

    protected $table = 'output_rfts_packing_po_return';

    protected $fillable = [
        'id',
        'output_rfts_packing_po_id',
        'master_plan_id',
        'ppic_master_id',
        'act_costing_id',
        'so_det_id',
        'po',
        'kpno',
        'style',
        'color',
        'size',
        'packing_line',
        'qty_return',
        'line_qc_finishing',
        'kode_numbering',
        'created_by',
        'created_by_username',
        'created_by_line',
        'created_at',
        'updated_at',
    ];
}
