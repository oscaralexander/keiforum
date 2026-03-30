<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::index')->name('home');
Route::livewire('algemene-voorwaarden', 'pages::terms')->name('terms');
Route::livewire('privacy', 'pages::privacy')->name('privacy');
Route::livewire('leden', 'pages::members.index')->name('members');
Route::livewire('agenda', 'pages::agenda.index')->name('agenda');

Route::get('img', App\Http\Controllers\ImageProxyController::class)->name('img');
Route::get('sitemap.xml', App\Http\Controllers\SitemapController::class)->name('sitemap');

// API
Route::match(['get', 'post'], 'api/users/search', App\Http\Controllers\Api\User\SearchController::class)->name('users.search');

Route::middleware('guest')->group(function () {
    Route::livewire('inloggen', 'pages::user.login')->name('login');
    Route::livewire('registreren', 'pages::user.register')->name('register');
    Route::livewire('registreren/google', 'pages::user.register-oauth')->name('register-oauth');
    Route::livewire('account-activeren/{token}', 'pages::user.activate-account')->name('activate-account');
});

// Google OAuth
Route::get('auth/google', [App\Http\Controllers\User\GoogleAuthController::class, 'redirect'])->name('auth.google');
Route::get('auth/google/callback', [App\Http\Controllers\User\GoogleAuthController::class, 'callback'])->name('auth.google.callback');

Route::middleware('auth')->group(function () {
    Route::livewire('admin', 'pages::admin.index')->name('admin');
    Route::livewire('berichten/{conversation_id?}', 'pages::conversations.index')->name('conversations');
    Route::livewire('instellingen', 'pages::user.settings')->name('settings');
    Route::livewire('profiel', 'pages::user.profile')->name('profile');
    Route::post('uitloggen', App\Http\Controllers\User\LogoutController::class)->name('logout');

    // Topic
    Route::livewire('nieuw-onderwerp/{forum?}', 'pages::topic.create')->name('topic.create');
});

// Member
Route::livewire('@{user}', 'pages::members.show')->name('member.show');

// Area
Route::livewire('in/{area}', 'pages::area.show')->name('area.show');

// Forum
Route::livewire('{forum}', 'pages::forum.show')->name('forum.show');

// Topic
Route::livewire('{forum}/{topic}/{slug?}', 'pages::topic.show')->name('topic.show');
