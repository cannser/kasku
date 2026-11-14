<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\ReadsQuery;
use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LogController extends Controller
{
    use ReadsQuery;

    public function index(Request $request): View
    {
        $q = mb_substr($this->qs($request, 'q'), 0, 50);

        $logs = ActivityLog::query()
            ->with('user:id,username')
            ->when($q !== '', function ($query) use ($q) {
                $like = '%' . addcslashes($q, '%_\\') . '%';
                $query->where(function ($w) use ($like) {
                    $w->where('action', 'like', $like)
                      ->orWhere('ip', 'like', $like)
                      ->orWhereHas('user', fn ($u) => $u->where('username', 'like', $like));
                });
            })
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.logs', compact('logs', 'q'));
    }
}
