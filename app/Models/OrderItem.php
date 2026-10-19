<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class OrderItem extends Model
{
    protected $fillable = ['batch_id','customer_id','item_name','qty','internal_note'];
    public function batch() { return $this->belongsTo(Batch::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
}
