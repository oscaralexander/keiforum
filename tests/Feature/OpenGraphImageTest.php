<?php

namespace Tests\Feature;

use App\Enums\HeadlineVerdict;
use App\Lib\OpenGraphImage;
use App\Models\Headline;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OpenGraphImageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $photo = imagecreatetruecolor(800, 400);
        imagefill($photo, 0, 0, imagecolorallocate($photo, 20, 120, 220));
        ob_start();
        imagejpeg($photo);

        Http::fake([
            'photos.test/broken.jpg' => Http::response('', 500),
            'photos.test/*' => Http::response((string) ob_get_clean(), 200, ['Content-Type' => 'image/jpeg']),
        ]);
    }

    private function topic(string $body = '<p>Hallo</p>', array $attributes = []): Topic
    {
        $topic = Topic::factory()->create([
            'user_id' => User::factory()->create(['username' => 'anne'])->id,
            ...$attributes,
        ]);
        Post::factory()->create(['topic_id' => $topic->id, 'user_id' => $topic->user_id, 'body' => $body]);

        return $topic->fresh();
    }

    private function pixel(string $jpeg, int $x, int $y): array
    {
        $image = imagecreatefromstring($jpeg);
        $color = imagecolorat($image, $x, $y);

        return [($color >> 16) & 0xFF, ($color >> 8) & 0xFF, $color & 0xFF];
    }

    public function test_image_route_serves_a_1200_by_630_jpeg(): void
    {
        $topic = $this->topic();

        $response = $this->get(app(OpenGraphImage::class)->url($topic));

        $response->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        [$width, $height] = getimagesizefromstring($response->getContent());
        $this->assertSame([1200, 630], [$width, $height]);
    }

    public function test_topic_without_image_has_red_background(): void
    {
        [$red, $green, $blue] = $this->pixel(app(OpenGraphImage::class)->jpeg($this->topic()), 10, 10);

        $this->assertEqualsWithDelta(201, $red, 6);
        $this->assertEqualsWithDelta(48, $green, 6);
        $this->assertEqualsWithDelta(32, $blue, 6);
    }

    public function test_first_image_in_opening_post_is_the_background(): void
    {
        $topic = $this->topic('<p>Kijk</p><p><a href="https://photos.test/fiets.jpg" data-embed="true">https://photos.test/fiets.jpg</a></p>');

        $this->assertSame('https://photos.test/fiets.jpg', $topic->openingImageUrl());

        [$red, $green, $blue] = $this->pixel(app(OpenGraphImage::class)->jpeg($topic), 10, 10);

        $this->assertGreaterThan($red, $blue, 'The blue photo, darkened, should show instead of the red background.');
    }

    public function test_news_article_image_is_the_background(): void
    {
        $topic = $this->topic();
        Headline::factory()->verdict(HeadlineVerdict::APPROVED)->create([
            'topic_id' => $topic->id,
            'article_image_url' => 'https://photos.test/artikel.jpg',
        ]);

        $this->assertSame('https://photos.test/artikel.jpg', $topic->fresh()->openingImageUrl());
    }

    public function test_article_image_is_not_used_when_disabled(): void
    {
        config(['news.show_article_image' => false]);
        $topic = $this->topic();
        Headline::factory()->verdict(HeadlineVerdict::APPROVED)->create([
            'topic_id' => $topic->id,
            'article_image_url' => 'https://photos.test/artikel.jpg',
        ]);

        $this->assertNull($topic->fresh()->openingImageUrl());
    }

    public function test_default_background_is_used_without_image(): void
    {
        config(['opengraph.default_background' => 'resources/img/nieuwsplein33.png']);
        $topic = $this->topic();

        $this->assertSame('resources/img/nieuwsplein33.png', app(OpenGraphImage::class)->backgroundSource($topic));

        [$red, $green, $blue] = $this->pixel(app(OpenGraphImage::class)->jpeg($topic), 10, 10);

        $this->assertLessThan(150, $red, 'The darkened default background should show instead of the red background.');
    }

    public function test_broken_background_falls_back_to_red(): void
    {
        $topic = $this->topic('<p><a href="https://photos.test/broken.jpg">https://photos.test/broken.jpg</a></p>');

        [$red] = $this->pixel(app(OpenGraphImage::class)->jpeg($topic), 10, 10);

        $this->assertEqualsWithDelta(201, $red, 6);
    }

    public function test_image_is_cached_until_the_topic_changes(): void
    {
        $topic = $this->topic('<p><a href="https://photos.test/fiets.jpg">https://photos.test/fiets.jpg</a></p>');
        $openGraphImage = app(OpenGraphImage::class);

        $openGraphImage->jpeg($topic);
        $openGraphImage->jpeg($topic);
        Http::assertSentCount(1);

        $oldPath = $openGraphImage->cachePath($topic);
        $topic->update(['title' => 'Een nieuwe titel']);
        $newPath = $openGraphImage->cachePath($topic);
        $openGraphImage->jpeg($topic);

        $this->assertNotSame($oldPath, $newPath);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($newPath);
    }

    public function test_long_titles_are_wrapped_and_truncated(): void
    {
        $lines = app(OpenGraphImage::class)->titleLines(str_repeat('Amersfoortse gemeenteraad worstelt met windmolens ', 6));

        $this->assertCount(5, $lines);
        $this->assertStringEndsWith('…', $lines[4]);
    }

    public function test_short_titles_are_not_truncated(): void
    {
        $this->assertSame(['Dit is een test'], app(OpenGraphImage::class)->titleLines('Dit is een test'));
    }

    public function test_topic_page_links_the_generated_image(): void
    {
        $topic = $this->topic();
        $url = app(OpenGraphImage::class)->url($topic);

        $this->get(route('topic.show', [$topic->forum, $topic, $topic->slug]))
            ->assertOk()
            ->assertSee('<meta content="'.e($url).'" property="og:image">', false)
            ->assertSee('<meta content="1200" property="og:image:width">', false)
            ->assertSee('<meta content="summary_large_image" name="twitter:card">', false)
            ->assertDontSee('og-image-1.png', false);
    }

    public function test_other_pages_keep_the_default_image(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('og-image-1.png', false)
            ->assertDontSee('og:image:width', false);
    }

    public function test_deleted_topic_has_no_image(): void
    {
        $topic = $this->topic();
        $url = app(OpenGraphImage::class)->url($topic);
        $topic->delete();

        $this->get($url)->assertNotFound();
    }
}
