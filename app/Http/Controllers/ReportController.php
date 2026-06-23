<?php

namespace App\Http\Controllers;

use App\Models\KpiCard;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $cards = KpiCard::query()
            ->where('is_active', true)
            ->orderBy('display_order')
            ->get(['id', 'title', 'slug', 'category', 'total_marks']);

        return view('reports.index', [
            'cards' => $cards,
            'user' => $request->user(),
        ]);
    }
}
