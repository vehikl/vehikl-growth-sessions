<?php

namespace App\Http\Controllers;

use App\Models\Tag;

class TagsController extends Controller
{
    public function index()
    {
        return Tag::all();
    }
}
