<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class TrackingHistory extends Model
{
    public $timestamps = false;
    protected $fillable = ['batch_id','from_status','to_status','changed_by','created_at'];
    protected function casts(): array { return ['created_at'=>'datetime']; }
}
