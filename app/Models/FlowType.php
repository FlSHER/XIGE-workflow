<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FlowType extends Model
{
    use SoftDeletes;

    protected $fillable = ['name','sort'];
    protected $hidden = ['deleted_at'];

    public function flow(){
        return $this->hasMany(Flow::class,'flow_type_id');
    }
}
