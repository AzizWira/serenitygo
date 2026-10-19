<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class CurrencyRate extends Model
{
    protected $fillable = ['currency','label','idr_rate','fee_type','fee_value','personal_order_fee_idr','request_sharing_fee_idr','is_active'];
    protected function casts(): array { return ['is_active'=>'boolean','idr_rate'=>'decimal:6','fee_value'=>'decimal:2','personal_order_fee_idr'=>'decimal:2','request_sharing_fee_idr'=>'decimal:2']; }
}
