<?php

namespace Modules\MCH\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MCHController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('mch::index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('mch::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('mch::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('mch::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
