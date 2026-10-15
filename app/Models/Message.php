<?php

namespace App\Models;

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
        $body = html_entity_decode(strip_tags($this->body_transformed), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return new Attribute(
            get: fn () => trim($body),
        );
    }

    public function bodyTransformed(): Attribute
    {
        $body = preg_replace(
            '/(https?:\/\/(?:(?!&quot;|&#039;)[^\s<])+)/i',
            '<a href="$1" rel="nofollow" target="_blank">$1</a>',
            e($this->body)
        );
        $body = preg_replace('/\*([^\r\n*]+)\*/', '<b>$1</b>', $body);
        $body = preg_replace('/\_([^\r\n*]+)\_/', '<i>$1</i>', $body);
        $body = nl2br($body);
        $body = '<p>'.$body.'</p>';

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
