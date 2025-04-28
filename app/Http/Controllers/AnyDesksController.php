<?php

namespace App\Http\Controllers;

use App\Models\AnyDesk;
use App\Policies\GrowthSessionPolicy;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class AnyDesksController extends Controller
{
    public function index()
    {
        abort_unless((new GrowthSessionPolicy())->viewAnyDesks(request()->user()), ResponseAlias::HTTP_NOT_FOUND);
        return AnyDesk::all();
    }
}
