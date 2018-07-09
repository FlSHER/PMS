<?php 

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Models\ArtisanCommandLog as CommandLogModel;

class CommandLogController extends Controller
{
    /**
     * 获取任务执行记录.
     * 
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $status = $request->query('status');
        $stime = $request->query('stime');
        $etime = $request->query('etime');

        $logs = CommandLogModel::query()
            ->when(isset($status), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when(($stime && $etime), function ($query) use ($stime, $etime) {
                $query->whereBetween('created_at', [$stime, $etime]);
            })
            ->latest('id')
            ->FilterByQueryString()
            ->withPagination();

        return response()->json($logs, 200);
    }
    
}