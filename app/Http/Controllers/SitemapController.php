<?php

namespace App\Http\Controllers;

use App\Models\Forum;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $forums = Forum::query()->withCount('topics')->get();
        $topics = Topic::query()->withCount('posts')->with('forum')->get();
        $users = User::query()->select('username')->get();

        return response()
            ->view('sitemap', compact('forums', 'topics', 'users'))
            ->header('Content-Type', 'application/xml');
    }
}
