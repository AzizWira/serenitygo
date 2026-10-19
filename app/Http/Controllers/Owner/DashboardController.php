<?php
namespace App\Http\Controllers\Owner;
use App\Http\Controllers\Controller;
use App\Models\{Batch,OrderItem,TaxRecord};
use Illuminate\Support\Carbon;
class DashboardController extends Controller
{
    public function index()
    {
        $counts = [
            'batches'=>Batch::count(), 'items'=>OrderItem::sum('qty'),
            'active'=>Batch::whereNotIn('status',['Arrived admin','Arrived Admin'])->count(),
            'unfixed'=>TaxRecord::where('is_fixed',false)->count(),
        ];
        $batches = Batch::withCount('items')->latest()->limit(6)->get();
        $unpaid = TaxRecord::whereNull('paid_at')->whereNotNull('due_at')->where('due_at','<',Carbon::today('Asia/Jakarta'))->count();
        return view('owner.dashboard',compact('counts','batches','unpaid'));
    }
}
