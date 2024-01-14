<?php

namespace App\Http\Controllers;

use App\AnyDesk;
use App\Policies\GrowthSessionPolicy;
use App\Tag;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class TagsController extends Controller
{
    public function index()
    {
        return Tag::all();
    }
}
