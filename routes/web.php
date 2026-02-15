<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::index')->name('home');
Route::livewire('leden', 'pages::members.index')->name('members');
Route::livewire('agenda', 'pages::agenda.index')->name('agenda');

Route::get('img', App\Http\Controllers\ImageProxyController::class)->name('img');

// API
Route::match(['get', 'post'], 'api/users/search', App\Http\Controllers\Api\User\SearchController::class)->name('users.search');

Route::middleware('guest')->group(function () {
    Route::livewire('inloggen', 'pages::user.login')->name('login');
    Route::livewire('registreren', 'pages::user.register')->name('register');
    Route::livewire('account-activeren/{token}', 'pages::user.activate-account')->name('activate-account');
});

Route::middleware('auth')->group(function () {
    Route::livewire('berichten/nieuw', 'pages::messages.index')->name('messages.create');
    Route::livewire('berichten/{conversation?}', 'pages::messages.index')->name('messages');
    Route::livewire('instellingen', 'pages::user.settings')->name('settings');
    Route::livewire('profiel', 'pages::user.profile')->name('profile');
    Route::post('uitloggen', App\Http\Controllers\User\LogoutController::class)->name('logout');

    // Topic
    Route::livewire('{forum}/nieuw', 'pages::topic.create')->name('topic.create');
});

// User
Route::livewire('@{username}', 'pages::user.show')->name('user.show');

// Forum
Route::livewire('{forum}', 'pages::forum.show')->name('forum.show');

// Topic
Route::livewire('{forum}/{topic}/{slug?}', 'pages::topic.show')->name('topic.show');

Route::livewire('in/{area}', 'pages::area.show')->name('area.show');