<?php

namespace App\Models;

use App\Lib\EmbedTransformer;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    public const PAGINATE_COUNT = 10;

    protected $guarded = ['id'];

    protected $with = ['user', 'deletedBy'];

    public function bodyPlainText(): Attribute
    {
        $bodyPlainText = preg_replace('/<\/([ol|p|ul])>/', "<$1>\n\n", $this->body);
        $bodyPlainText = trim(strip_tags($bodyPlainText));

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
    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by_id');
    }

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

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
