<?php

namespace App\Http\Controllers;

use App\SocialMob;
use Illuminate\Http\Request;

class SocialMobController extends Controller
{
    public function index()
    {
        //
    }

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
        $request->user()->socialMobs()->save(new SocialMob($request->all()));
    }

    public function show(SocialMob $socialMob)
    {
        //
    }

    public function edit(SocialMob $socialMob)
    {
        //
    }

    public function update(Request $request, SocialMob $socialMob)
    {
        //
    }

    public function destroy(SocialMob $socialMob)
    {
        //
    }
}
