<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'prefix' => config('eligify.ui.route_prefix', 'eligify'),
    'as' => 'eligify.',
    'middleware' => array_merge(config('eligify.ui.middleware', ['web']), ['eligify.authorize']),
], function () {
    // Dashboard route (renders Blade view; Livewire components can be added later)
    Route::get('/', function () {
        $metrics = \CleaniqueCoders\Eligify\Actions\GetDashboardMetrics::run();

        return view('eligify::dashboard', compact('metrics'));
    })->name('dashboard');

    // Health check route (optional)
    Route::get('/_health', function () {
        return response()->json(['status' => 'ok']);
    })->name('health');

    // Criteria pages (Livewire-based)
    Route::get('/criteria', function () {
        return view('eligify::criteria.index');
    })->name('criteria.index');

    Route::get('/criteria/create', function () {
        return view('eligify::criteria.create');
    })->name('criteria.create');

    Route::get('/criteria/{id}', function (string $id) {
        return view('eligify::criteria.show', ['id' => $id]);
    })->name('criteria.show');

    Route::get('/criteria/{id}/edit', function (string $id) {
        return view('eligify::criteria.edit', ['id' => $id]);
    })->name('criteria.edit');

    // Rules pages (Livewire-based)
    Route::get('/rules/create', function () {
        $criteriaId = request('criteria_id');

        return view('eligify::rules.create', ['criteriaId' => $criteriaId]);
    })->name('rules.create');

    Route::get('/rules/{id}/edit', function (string $id) {
        return view('eligify::rules.edit', ['id' => $id]);
    })->name('rules.edit');

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
