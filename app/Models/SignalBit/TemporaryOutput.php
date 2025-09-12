<?php

namespace App\Models\SignalBit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TemporaryOutput extends Model
{
    use HasFactory;

    protected $connection = 'mysql_sb';

    protected $table = 'temporary_output_packing';

    protected $guarded = [];

    public function userPassword()
    {
        return $this->hasMany(UserPassword::class, 'line_id', 'line_id');
    }
}
