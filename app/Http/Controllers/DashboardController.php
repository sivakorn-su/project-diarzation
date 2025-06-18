<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $meetings = Meeting::latest()->with(['user'])->get();
        $totalMeetings = Meeting::count();
        $totalUsers = User::count();

        return Inertia::render('Dashboard', [
            'meetings' => $meetings,
            'totalMeetings' => $totalMeetings,
            'totalUsers' => $totalUsers,
        ]);
    }
} 