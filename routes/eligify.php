<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('eligify.ui.route_prefix', 'eligify'),
    'as' => 'eligify.',
    'middleware' => array_merge(config('eligify.ui.middleware', ['web']), ['eligify.authorize']),
], function () {
    // Dashboard route (renders Blade view; Livewire components can be added later)
    Route::get('/', function () {
        return view('eligify::dashboard');
    })->name('dashboard');

    // Health check route (optional)
    Route::get('/_health', function () {
        return response()->json(['status' => 'ok']);
    })->name('health');

    // Criteria pages (placeholders)
    Route::get('/criteria', function () {
        return view('eligify::criteria.index');
    })->name('criteria.index');

    Route::get('/criteria/{id}', function (string $id) {
        return view('eligify::criteria.show', ['id' => $id]);
    })->name('criteria.show');

    // Rule Library
    Route::get('/rule-library', function () {
        return view('eligify::rule-library.index');
    })->name('rule-library.index');

    // Playground
    Route::get('/playground', function () {
        return view('eligify::playground');
    })->name('playground');

    // Audit
    Route::get('/audit', function () {
        return view('eligify::audit');
    })->name('audit');

    // Settings
    Route::get('/settings', function () {
        return view('eligify::settings');
    })->name('settings');
});
