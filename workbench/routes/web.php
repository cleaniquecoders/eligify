<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // Redirect to the Eligify dashboard when workbench root is visited
    return redirect()->route('eligify.dashboard');
});
