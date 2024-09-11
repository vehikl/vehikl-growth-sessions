<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ShowStatistics extends Controller
{
    public function __invoke(Request $request)
    {
        return view('statistics');
    }
}
