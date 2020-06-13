<?php

namespace App\Http\Controllers;

use App\Comment;
use App\SocialMob;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request, SocialMob $socialMob)
    {
        return $socialMob->comments;
    }

    public function store(Request $request, SocialMob $socialMob)
    {
        $comment = new Comment($request->all());
        $comment->user()->associate($request->user());
        $comment->socialMob()->associate($socialMob);
        $comment->save();
        return $comment;
    }
}
