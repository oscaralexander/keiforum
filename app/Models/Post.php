<?php

namespace App\Models;

use App\Lib\EmbedTransformer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Post extends Model
{
    use SoftDeletes;

    public const PAGINATE_COUNT = 10;

    protected $guarded = ['id'];

    protected $with = ['user'];

    public function bodyPlainText(): Attribute
    {
        $bodyPlainText = preg_replace('/<\/([ol|p|ul])>/', "<$1>\n\n", $this->body);
        $bodyPlainText = trim(strip_tags($bodyPlainText));
        
        // $bodyPlainText = Str::of($this->body)->replaceMatches('/<\/([ol|p|ul])>/', function (array $matches) {
        //     return "{$matches[0]}\n\n";
        // })->stripTags()->trim();

        return new Attribute(
            get: fn () => $bodyPlainText,
        );
    }

    public function bodyTransformed(): Attribute
    {
        return new Attribute(
            get: fn () => (new EmbedTransformer)->transform($this->body),
        );
    }

    /**
     * Relationships
     */

    public function forum(): BelongsTo
    {
        return $this->through('topic')->belongsTo(Forum::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

