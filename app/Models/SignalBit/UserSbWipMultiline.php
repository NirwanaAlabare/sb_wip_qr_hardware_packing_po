<?php

namespace App\Models\SignalBit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class UserSbWipMultiline extends Authenticatable
{
    use HasFactory;

    protected $connection = 'mysql_sb';

    protected $table = 'user_sb_wip_multiline';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'user_id',
        'line_id',
        'created_at',
        'updated_at'
    ];

    public function line()
    {
        return $this->belongsTo(UserPassword::class, 'line_id', 'line_id');
    }

    public function masterPlans()
    {
        return $this->hasManyThrough(
            MasterPlan::class,
            UserPassword::class,
            'line_id',      // Foreign key on userpassword
            'sewing_line',  // Foreign key on master_plan
            'line_id',      // Local key on multiline
            'line_id'       // Local key on userpassword
        );
    }
}
