<?php

namespace App\Models\SignalBit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reject extends Model
{
    use HasFactory;

    protected $connection = 'mysql_sb';

    protected $table = 'output_rejects_packing';

    protected $fillable = [
        'id',
        'master_plan_id',
        'so_det_id',
        'no_cut_size',
        'kode_numbering',
        'status',
        'defect_id',
        'reject_type_id',
        'reject_area_id',
        'reject_area_x',
        'reject_area_y',
        'reject_status',
        'created_at',
        'updated_at',
        'created_by',
    ];

    public function masterPlan()
    {
        return $this->belongsTo(MasterPlan::class, 'master_plan_id', 'id');
    }

    public function defect()
    {
        return $this->hasOne(Defect::class, 'id', 'defect_id');
    }

    public function defectType()
    {
        return $this->belongsTo(DefectType::class, 'reject_type_id', 'id');
    }

    public function defectArea()
    {
        return $this->belongsTo(DefectArea::class, 'reject_area_id', 'id');
    }

    public function undo()
    {
        return $this->hasOne(Undo::class, 'output_reject_id', 'id');
    }
}
