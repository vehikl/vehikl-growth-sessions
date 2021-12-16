<?php

namespace App\Http\Controllers;

use App\NodeGraph;
use Illuminate\Http\Request;

class NodeGraphController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
//       return NodeGraph::getData();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\NodeGraph  $nodeGraph
     * @return \Illuminate\Http\Response
     */
    public function show(NodeGraph $nodeGraph)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\NodeGraph  $nodeGraph
     * @return \Illuminate\Http\Response
     */
    public function edit(NodeGraph $nodeGraph)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\NodeGraph  $nodeGraph
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, NodeGraph $nodeGraph)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\NodeGraph  $nodeGraph
     * @return \Illuminate\Http\Response
     */
    public function destroy(NodeGraph $nodeGraph)
    {
        //
    }
}
