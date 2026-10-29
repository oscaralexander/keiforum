<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Forum;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $forums = Forum::query()->listed()->orderBy('position')->withCount(['topics' => fn ($query) => $query->visible()])->get();
        $topics = Topic::query()->visible()->withCount('posts')->withMax('posts', 'created_at')->with('forum')->get();
        $activities = Activity::query()->published()->upcoming()->with('user')->get();
        $organizers = $activities->pluck('user')->unique('id')->sortBy('username');
        $users = User::query()->active()->members()->has('posts', '>=', User::INDEXABLE_POSTS_COUNT)->select('username')->get();

        return response()
            ->view('sitemap', compact('activities', 'forums', 'organizers', 'topics', 'users'))
            ->header('Content-Type', 'application/xml');
    }
}
