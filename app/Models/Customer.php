<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Customer extends Model
{
    protected $fillable = ['name'];
    public function items() { return $this->hasMany(OrderItem::class); }
}
