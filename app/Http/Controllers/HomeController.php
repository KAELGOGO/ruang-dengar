<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{
    /** One dashboard; resources/views/home.blade.php shows the menu for the user's role. */
    public function index()
    {
        return view('home', ['user' => auth()->user()->load('listenerProfile')]);
    }
}
