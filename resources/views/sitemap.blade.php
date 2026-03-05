<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    {{-- Static pages --}}
    <url>
        <loc>{{ route('home') }}</loc>
        <changefreq>daily</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc>{{ route('members') }}</loc>
        <changefreq>weekly</changefreq>
        <priority>0.6</priority>
    </url>
    <url>
        <loc>{{ route('agenda') }}</loc>
        <changefreq>weekly</changefreq>
        <priority>0.6</priority>
    </url>

    {{-- Forum pages (paginated) --}}
    @foreach ($forums as $forum)
        @php $forumPages = max(1, (int) ceil($forum->topics_count / \App\Models\Topic::PAGINATE_COUNT)); @endphp
        <url>
            <loc>{{ route('forum.show', $forum) }}</loc>
            <changefreq>daily</changefreq>
            <priority>0.8</priority>
        </url>
        @for ($page = 2; $page <= $forumPages; $page++)
            <url>
                <loc>{{ route('forum.show', $forum) }}?p={{ $page }}</loc>
                <changefreq>daily</changefreq>
                <priority>0.7</priority>
            </url>
        @endfor
    @endforeach

    {{-- Topic pages (paginated) --}}
    @foreach ($topics as $topic)
        @php $topicPages = max(1, (int) ceil($topic->posts_count / \App\Models\Post::PAGINATE_COUNT)); @endphp
        <url>
            <loc>{{ route('topic.show', [$topic->forum, $topic, $topic->slug]) }}</loc>
            <changefreq>weekly</changefreq>
            <priority>0.6</priority>
        </url>
        @for ($page = 2; $page <= $topicPages; $page++)
            <url>
                <loc>{{ route('topic.show', [$topic->forum, $topic, $topic->slug]) }}?p={{ $page }}</loc>
                <changefreq>weekly</changefreq>
                <priority>0.5</priority>
            </url>
        @endfor
    @endforeach

    {{-- Member pages --}}
    @foreach ($users as $user)
        <url>
            <loc>{{ route('member.show', $user) }}</loc>
            <changefreq>monthly</changefreq>
            <priority>0.4</priority>
        </url>
    @endforeach
</urlset>
