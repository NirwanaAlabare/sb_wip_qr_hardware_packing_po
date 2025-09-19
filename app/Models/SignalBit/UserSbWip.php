<?php

namespace App\Models\SignalBit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\UserPassword as Authenticatable;

class UserSbWip extends Authenticatable
{
    use HasFactory;

    protected $connection = 'mysql_sb';

    protected $table = 'user_sb_wip';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'name',
        'username',
        'password',
        'line_id',
        'line_type',
        'remember_token',
        'created_at',
        'updated_at'
    ];

    public function line()
    {
        return $this->belongsTo(UserPassword::class, 'line_id', 'line_id');
    }

    public function multiLine()
    {
        return $this->hasMany(UserSbWipMultiline::class, 'user_id', 'id');
    }

    // public function multiLine()
    // {
    //     return $this->hasManyThrough(
    //         UserPassword::class,
    //         UserSbWipMultiline::class,
    //         'user_id',   // Foreign key on user_sb_wip_multiline
    //         'line_id',   // Foreign key on userpassword
    //         'id',        // Local key on user_sb_wip
    //         'line_id'    // Local key on user_sb_wip_multiline
    //     );
    // }

    public function masterPlans()
    {
        return $this->hasManyThrough(
            MasterPlan::class,
            UserPassword::class,
            'username', // Foreign key on the environments table...
            'sewing_line', // Foreign key on the deployments table...
            'line_id', // Local key on the projects table...
            'line_id' // Local key on the environments table...
        );
    }
}
