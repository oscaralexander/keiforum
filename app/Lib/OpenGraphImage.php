<?php

namespace App\Lib;

use App\Models\Topic;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Geometry\Factories\RectangleFactory;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Typography\Font;
use Intervention\Image\Typography\FontFactory;
use Throwable;

/**
 * Renders the 1200×630 Open Graph preview image of a topic, following the
 * "OpenGraph" page in the Keiforum Figma file.
 */
class OpenGraphImage
{
    public const WIDTH = 1200;

    public const HEIGHT = 630;

    /**
     * Bump to regenerate all images after changing the design.
     */
    public const VERSION = 1;

    public const CACHE_ROOT = 'og/topics';

    private const BACKGROUND_COLOR = '#c93020';

    private const CONTENT_X = 333;

    private const CONTENT_WIDTH = 534;

    private const LOGO_Y = 48;

    private const TITLE_Y = 152;

    private const TITLE_FONT_SIZE = 48;

    private const TITLE_LINE_HEIGHT = 60;

    private const TITLE_MAX_LINES = 5;

    private const FOOTER_Y = 518;

    private const AVATAR_SIZE = 64;

    private const USERNAME_X = 413;

    private const USERNAME_FONT_SIZE = 32;

    /**
     * The background photo is blurred at a quarter of its size, which matches
     * Figma's 12px layer blur and is much faster than blurring it full size.
     */
    private const BLUR_SCALE = 4;

    private const BLUR_AMOUNT = 6;

    private ImageManager $images;

    public function __construct()
    {
        $this->images = extension_loaded('imagick')
            ? new ImageManager(new ImagickDriver)
            : new ImageManager(new GdDriver);
    }

    /**
     * The cached image path on the public disk. It changes whenever anything
     * shown on the image changes, so platforms fetch the new version.
     */
    public function cachePath(Topic $topic): string
    {
        $user = $topic->user;

        $hash = md5(json_encode([
            self::VERSION,
            $topic->title,
            $user->username,
            $user->has_avatar ? $user->updated_at?->timestamp : null,
            $this->backgroundSource($topic),
        ]));

        return self::CACHE_ROOT.'/'.$topic->id.'-'.substr($hash, 0, 12).'.jpg';
    }

    /**
     * The public URL, versioned with the cache path so platforms fetch a new
     * image when it changes.
     */
    public function url(Topic $topic): string
    {
        return route('og.topic', [
            'topic' => $topic,
            'v' => pathinfo($this->cachePath($topic), PATHINFO_FILENAME),
        ]);
    }

    /**
     * Render the image, or return the cached copy.
     */
    public function jpeg(Topic $topic): string
    {
        $disk = Storage::disk('public');
        $path = $this->cachePath($topic);

        if ($disk->exists($path)) {
            return $disk->get($path);
        }

        $contents = $this->render($topic)->toJpeg(quality: 85)->toString();

        $staleFiles = array_filter(
            $disk->files(self::CACHE_ROOT),
            fn (string $file) => $file !== $path && str_starts_with(basename($file), $topic->id.'-'),
        );

        $disk->delete($staleFiles);
        $disk->put($path, $contents);

        return $contents;
    }

    public function render(Topic $topic): ImageInterface
    {
        $image = $this->images->create(self::WIDTH, self::HEIGHT)->fill(self::BACKGROUND_COLOR);

        if ($background = $this->backgroundImage($topic)) {
            $image->place($background);
            $image->drawRectangle(0, 0, function (RectangleFactory $rectangle): void {
                $rectangle->size(self::WIDTH, self::HEIGHT);
                $rectangle->background('rgba(0, 0, 0, 0.5)');
            });
        }

        $image->place(resource_path('img/og/logo.png'), 'top-left', self::CONTENT_X, self::LOGO_Y);

        foreach ($this->titleLines($topic->title) as $index => $line) {
            $image->text($line, self::CONTENT_X, self::TITLE_Y + ($index * self::TITLE_LINE_HEIGHT) + (self::TITLE_LINE_HEIGHT / 2), function (FontFactory $font): void {
                $font->filename($this->titleFont());
                $font->size(self::TITLE_FONT_SIZE);
                $font->color('#ffffff');
                $font->valign('middle');
            });
        }

        if ($avatar = $this->avatar($topic)) {
            $image->place($avatar, 'top-left', self::CONTENT_X, self::FOOTER_Y);
        }

        $image->text($topic->user->username, self::USERNAME_X, self::FOOTER_Y + (self::AVATAR_SIZE / 2), function (FontFactory $font): void {
            $font->filename(resource_path('fonts/og/Inter-SemiBold.ttf'));
            $font->size(self::USERNAME_FONT_SIZE);
            $font->color('#ffffff');
            $font->valign('middle');
        });

        return $image;
    }

    /**
     * The opening post's image, the default background, or none.
     */
    public function backgroundSource(Topic $topic): ?string
    {
        return $topic->openingImageUrl() ?? config('opengraph.default_background');
    }

    /**
     * Word-wrap the title to the content width, ending in an ellipsis when it
     * doesn't fit in the maximum number of lines.
     *
     * @return list<string>
     */
    public function titleLines(string $title): array
    {
        $font = (new Font($this->titleFont()))->setSize(self::TITLE_FONT_SIZE);
        $fits = fn (string $text): bool => $this->images->driver()->fontProcessor()->boxSize($text, $font)->width() <= self::CONTENT_WIDTH;

        $lines = [];
        $line = '';

        foreach (preg_split('/\s+/', trim($title)) as $word) {
            $candidate = $line === '' ? $word : $line.' '.$word;

            if ($line === '' || $fits($candidate)) {
                $line = $candidate;

                continue;
            }

            $lines[] = $line;
            $line = $word;
        }

        $lines[] = $line;

        if (count($lines) <= self::TITLE_MAX_LINES) {
            return $lines;
        }

        $lines = array_slice($lines, 0, self::TITLE_MAX_LINES);
        $last = $lines[self::TITLE_MAX_LINES - 1];

        while ($last !== '' && ! $fits($last.'…')) {
            $last = preg_replace('/\s*\S+$/', '', $last);
        }

        $lines[self::TITLE_MAX_LINES - 1] = rtrim($last, ' ,.:;-').'…';

        return $lines;
    }

    private function titleFont(): string
    {
        return resource_path('fonts/og/Parkinsans-Bold.ttf');
    }

    /**
     * A missing or broken background image falls back to the plain background.
     */
    private function backgroundImage(Topic $topic): ?ImageInterface
    {
        $source = $this->backgroundSource($topic);

        if (! $source) {
            return null;
        }

        try {
            $contents = filter_var($source, FILTER_VALIDATE_URL)
                ? Http::timeout(5)->accept('image/*')->get($source)->throw()->body()
                : file_get_contents(base_path($source));

            $smallWidth = intdiv(self::WIDTH, self::BLUR_SCALE);
            $smallHeight = intdiv(self::HEIGHT, self::BLUR_SCALE);

            return $this->images->read($contents)
                ->cover($smallWidth, $smallHeight)
                ->blur(self::BLUR_AMOUNT)
                ->resize(self::WIDTH, self::HEIGHT);
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    /**
     * The topic starter's avatar as a circle with smooth edges.
     */
    private function avatar(Topic $topic): ?string
    {
        $user = $topic->user;
        $path = $user->has_avatar
            ? Storage::disk('public')->path($user->avatar)
            : public_path('assets/img/avatar/'.$user->avatarInitial().'.png');

        if (! is_file($path)) {
            return null;
        }

        $square = $this->images->read($path)->cover(self::AVATAR_SIZE, self::AVATAR_SIZE)->toPng()->toString();
        $circle = imagecreatefromstring($square);
        imagealphablending($circle, false);
        imagesavealpha($circle, true);

        $radius = self::AVATAR_SIZE / 2;

        for ($x = 0; $x < self::AVATAR_SIZE; $x++) {
            for ($y = 0; $y < self::AVATAR_SIZE; $y++) {
                $coverage = max(0, min(1, $radius - hypot($x + 0.5 - $radius, $y + 0.5 - $radius) + 0.5));
                $color = imagecolorsforindex($circle, imagecolorat($circle, $x, $y));
                $alpha = (int) round(127 - ((127 - $color['alpha']) * $coverage));

                imagesetpixel($circle, $x, $y, imagecolorallocatealpha($circle, $color['red'], $color['green'], $color['blue'], $alpha));
            }
        }

        ob_start();
        imagepng($circle);
        imagedestroy($circle);

        return (string) ob_get_clean();
    }
}
