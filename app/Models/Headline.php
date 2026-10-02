<?php

namespace App\Models;

use App\Enums\HeadlineVerdict;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Headline extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'pub_date' => 'datetime',
            'verdict' => HeadlineVerdict::class,
        ];
    }

    /**
     * Extract the Nieuwsplein33 article ID from an article URL, e.g.
     * https://www.nieuwsplein33.nl/nieuws/4097209/some-slug
     */
    public static function articleIdFromLink(string $link): ?int
    {
        if (preg_match('#/nieuws/(\d+)#', $link, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Whether the article image should be shown above the topic's opening post.
     */
    public function showsArticleImage(): bool
    {
        return config('news.show_article_image') && $this->article_image_url !== null;
    }

    /**
     * Relationships
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }
}
