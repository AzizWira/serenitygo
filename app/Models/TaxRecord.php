<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
class TaxRecord extends Model
{
    protected $fillable = ['batch_id','customer_id','amount_idr','is_fixed','posted_at','due_at','paid_at','note'];
    protected function casts(): array { return ['amount_idr'=>'decimal:2','is_fixed'=>'boolean','posted_at'=>'date','due_at'=>'date','paid_at'=>'date']; }
    public function batch() { return $this->belongsTo(Batch::class); }
    public function customer() { return $this->belongsTo(Customer::class); }
    public function getDaysSincePostedAttribute(): ?int { return $this->posted_at ? max(0, (int) $this->posted_at->startOfDay()->diffInDays(Carbon::today('Asia/Jakarta'), false)) : null; }
    public function getDaysOverdueAttribute(): ?int { return (!$this->paid_at && $this->due_at) ? max(0, (int) $this->due_at->startOfDay()->diffInDays(Carbon::today('Asia/Jakarta'), false)) : null; }
}
