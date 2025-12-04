<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Splash', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('student/profile', function () {
    return Inertia::render('student/Profile');
})->middleware(['auth', 'verified'])->name('student.profile');

Route::get('student/content', function () {
    return Inertia::render('student/Content');
})->middleware(['auth', 'verified'])->name('student.content');

Route::get('student/content/{id}', function ($id) {
    return Inertia::render('student/ContentDetail', ['id' => $id]);
})->middleware(['auth', 'verified'])->name('student.content.detail');

Route::get('student/recommendations', function () {
    return Inertia::render('student/Recommendations');
})->middleware(['auth', 'verified'])->name('student.recommendations');

Route::get('student/assistant', function () {
    return Inertia::render('student/Assistant');
})->middleware(['auth', 'verified'])->name('student.assistant');

Route::get('student/saved', function () {
    return Inertia::render('student/Saved');
})->middleware(['auth', 'verified'])->name('student.saved');

Route::get('teacher/content', function () {
    return Inertia::render('teacher/Content');
})->middleware(['auth', 'verified'])->name('teacher.content');

Route::get('teacher/content/create', function () {
    return Inertia::render('teacher/ContentCreate');
})->middleware(['auth', 'verified'])->name('teacher.content.create');

Route::get('teacher/content/{id}/edit', function ($id) {
    return Inertia::render('teacher/ContentEdit', ['id' => $id]);
})->middleware(['auth', 'verified'])->name('teacher.content.edit');

Route::get('teacher/assistant', function () {
    return Inertia::render('teacher/Assistant');
})->middleware(['auth', 'verified'])->name('teacher.assistant');

Route::get('teacher/analytics', function () {
    return Inertia::render('teacher/Analytics');
})->middleware(['auth', 'verified'])->name('teacher.analytics');

require __DIR__.'/settings.php';
