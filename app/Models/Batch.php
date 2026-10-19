<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Batch extends Model
{
    protected $fillable = ['code','origin','status','show_items_public','show_customer_names_public','status_updated_at','internal_note'];
    protected function casts(): array { return ['show_items_public'=>'boolean','show_customer_names_public'=>'boolean','status_updated_at'=>'datetime']; }
    public function items() { return $this->hasMany(OrderItem::class); }
    public function histories() { return $this->hasMany(TrackingHistory::class)->orderByDesc('created_at'); }
    public function taxes() { return $this->hasMany(TaxRecord::class); }
}
