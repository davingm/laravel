<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(): View
    {
        abort_unless(request()->user()->role === 'administrator', 403);

        return view('pages.activity.index', [
            'activities' => ActivityLog::query()->latest('created_at')->paginate(30),
        ]);
    }
}
