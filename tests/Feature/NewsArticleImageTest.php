<?php

namespace Tests\Feature;

use App\Enums\HeadlineVerdict;
use App\Lib\NewsArticleImage;
use App\Models\Headline;
use App\Models\Post;
use App\Models\Topic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class NewsArticleImageTest extends TestCase
{
    use RefreshDatabase;

    private function imageComponent(string $src, ?string $caption, ?string $credit): string
    {
        return '<div __component="api.api-image" class="component"><figure><div class="inner">'
            .'<img alt="" src="'.$src.'"></div></figure>'
            .'<figcaption class="figcaption">'
            .($caption !== null ? '<span aria-hidden="true" class="description">'.$caption.'</span> ' : '')
            .($credit !== null ? '<span class="copyright">'.$credit.'</span>' : '')
            .'</figcaption></div>';
    }

    private function createNewsTopic(array $headlineAttributes = []): Topic
    {
        $topic = Topic::factory()->create();
        Post::factory()->create(['topic_id' => $topic->id, 'user_id' => $topic->user_id]);
        Headline::factory()->verdict(HeadlineVerdict::APPROVED)->create([
            'topic_id' => $topic->id,
            'article_image_url' => 'https://i.regiogroei.cloud/abc.jpg?width=1104',
            'article_image_caption' => 'De plekken waar de windmolens komen',
            'article_image_credit' => '© RTV Utrecht',
            ...$headlineAttributes,
        ]);

        return $topic->refresh();
    }

    private function renderFirstPost(Topic $topic): Testable
    {
        return Livewire::test('post', ['post' => $topic->firstPost, 'isFirstPost' => true, 'number' => 1]);
    }

    public function test_parses_image_caption_and_credit(): void
    {
        $html = '<html><body>'.$this->imageComponent('https://i.regiogroei.cloud/abc.jpg?width=1104', 'De windmolens', '© RTV Utrecht').'</body></html>';

        $this->assertSame([
            'url' => 'https://i.regiogroei.cloud/abc.jpg?width=1104',
            'caption' => 'De windmolens',
            'credit' => '© RTV Utrecht',
        ], (new NewsArticleImage)->parse($html));
    }

    public function test_prefers_image_matching_feed_enclosure(): void
    {
        $html = '<html><body>'
            .$this->imageComponent('https://i.regiogroei.cloud/other.jpg?width=1104', 'Ander beeld', '© Iemand')
            .$this->imageComponent('https://i.regiogroei.cloud/lead.jpg?width=1104', 'Hoofdbeeld', '© RTV Utrecht')
            .'</body></html>';

        $image = (new NewsArticleImage)->parse($html, 'https://i.regiogroei.cloud/lead.jpg?width=552&height=310');

        $this->assertSame('Hoofdbeeld', $image['caption']);
    }

    public function test_falls_back_to_first_image(): void
    {
        $html = '<html><body>'
            .$this->imageComponent('https://i.regiogroei.cloud/first.jpg', 'Eerste', '© A')
            .$this->imageComponent('https://i.regiogroei.cloud/second.jpg', 'Tweede', '© B')
            .'</body></html>';

        $this->assertSame('Eerste', (new NewsArticleImage)->parse($html, 'https://i.regiogroei.cloud/unknown.jpg')['caption']);
    }

    public function test_missing_caption_is_null(): void
    {
        $html = '<html><body>'.$this->imageComponent('https://i.regiogroei.cloud/abc.jpg', null, '© Nieuwsplein33').'</body></html>';

        $image = (new NewsArticleImage)->parse($html);

        $this->assertNull($image['caption']);
        $this->assertSame('© Nieuwsplein33', $image['credit']);
    }

    public function test_page_without_image_returns_null(): void
    {
        $this->assertNull((new NewsArticleImage)->parse('<html><body><p>Geen afbeelding</p></body></html>'));
    }

    public function test_first_post_shows_image_with_caption_and_credit(): void
    {
        $topic = $this->createNewsTopic();

        $this->renderFirstPost($topic)
            ->assertSee('post__image', false)
            ->assertSee(route('img', ['src' => 'https://i.regiogroei.cloud/abc.jpg?width=1104', 'w' => 1104, 'q' => 80]))
            ->assertSee('De plekken waar de windmolens komen')
            ->assertSee('© RTV Utrecht');
    }

    public function test_image_is_hidden_when_disabled_in_config(): void
    {
        config(['news.show_article_image' => false]);
        $topic = $this->createNewsTopic();

        $this->renderFirstPost($topic)
            ->assertDontSee('post__image', false)
            ->assertDontSee('© RTV Utrecht');
    }

    public function test_no_image_without_scraped_article_image(): void
    {
        $topic = $this->createNewsTopic(['article_image_url' => null]);

        $this->renderFirstPost($topic)->assertDontSee('post__image', false);
    }

    public function test_replies_do_not_show_image(): void
    {
        $topic = $this->createNewsTopic();
        $reply = Post::factory()->create(['topic_id' => $topic->id]);

        Livewire::test('post', ['post' => $reply, 'isFirstPost' => false, 'number' => 2])
            ->assertDontSee('post__image', false);
    }

    public function test_image_proxy_serves_image_with_only_a_width(): void
    {
        Storage::fake('public');

        $image = imagecreatetruecolor(40, 20);
        ob_start();
        imagepng($image);
        $png = (string) ob_get_clean();

        Http::fake(['i.regiogroei.cloud/*' => Http::response($png, 200, ['Content-Type' => 'image/png'])]);

        $this->get(route('img', ['src' => 'https://i.regiogroei.cloud/abc.jpg?width=1104', 'w' => 20]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/webp');
    }
}
