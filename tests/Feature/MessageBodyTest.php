<?php

namespace Tests\Feature;

use App\Models\Message;
use Tests\TestCase;

class MessageBodyTest extends TestCase
{
    public function test_html_in_messages_is_escaped(): void
    {
        $message = new Message(['body' => '<img src=x onerror=alert(1)><script>alert(2)</script>']);

        $this->assertSame(
            '<p>&lt;img src=x onerror=alert(1)&gt;&lt;script&gt;alert(2)&lt;/script&gt;</p>',
            $message->body_transformed
        );
    }

    public function test_urls_are_linked_without_breaking_out_of_the_attribute(): void
    {
        $message = new Message(['body' => 'Kijk "https://example.com/?a=1&b=2" nu']);

        $this->assertSame(
            '<p>Kijk &quot;<a href="https://example.com/?a=1&amp;b=2" rel="nofollow" target="_blank">https://example.com/?a=1&amp;b=2</a>&quot; nu</p>',
            $message->body_transformed
        );
    }

    public function test_bold_italic_and_line_breaks_still_work(): void
    {
        $message = new Message(['body' => "*vet* en _schuin_\nnieuwe regel"]);

        $this->assertSame("<p><b>vet</b> en <i>schuin</i><br />\nnieuwe regel</p>", $message->body_transformed);
    }

    public function test_plain_text_is_decoded(): void
    {
        $message = new Message(['body' => 'Tom & Jerry <3']);

        $this->assertSame('Tom & Jerry <3', $message->body_plain_text);
    }
}
