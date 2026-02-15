<?php

namespace App\Models;

use App\Lib\EmbedTransformer;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use SoftDeletes;

    public const PAGINATE_COUNT = 25;

    protected $guarded = ['id'];

    protected $with = ['user'];

    public function bodyPlainText(): Attribute
    {
        $body = trim(strip_tags($this->body_transformed));

        return new Attribute(
            get: fn () => trim(strip_tags($body)),
        );
    }

    public function bodyTransformed(): Attribute
    {
        $body = preg_replace(
            '/(https?:\/\/[^\s<]+)/i',
            '<a href="$1" rel="nofollow" target="_blank">$1</a>',
            $this->body
        );
        $body = preg_replace('/\*([^\r\n*]+)\*/', '<b>$1</b>', $body);
        $body = preg_replace('/\_([^\r\n*]+)\_/', '<i>$1</i>', $body);
        $body = nl2br($body);
        $body = '<p>' . $body . '</p>';
        
        return new Attribute(
            get: fn () => $body,
        );
    }

    /**
     * Relationships
     */

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
