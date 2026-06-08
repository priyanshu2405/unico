<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DisputesController extends Controller
{
    public function index()
    {
        if (auth()->user()->isManager() && !auth()->user()->can_view_disputes) {
            abort(403, 'Unauthorized access to disputes.');
        }
        if (auth()->user()->isSupport() && !auth()->user()->can_view_disputes) {
            abort(403, 'Unauthorized access to disputes.');
        }

        return view('dashboard.disputes.index');
    }
}
