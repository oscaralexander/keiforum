<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BuildIconSprite extends Command
{
    protected $signature = 'icons:build
                            {--source=resources/icons : Path to icon components}
                            {--output=public/assets/img/icons.svg : Output file path}';

    protected $description = 'Build a single SVG sprite from icon components using <symbol> tags';

    public function handle(): int
    {
        $sourcePath = base_path($this->option('source'));
        $outputPath = base_path($this->option('output'));

        if (! is_dir($sourcePath)) {
            $this->error("Source directory does not exist: {$sourcePath}");

            return self::FAILURE;
        }

        $symbols = [];
        $files = File::glob($sourcePath.'/*.svg');

        foreach ($files as $file) {
            $filename = basename($file, '.svg');
            $content = File::get($file);
            $symbol = $this->svgToSymbol($content, $filename);

            if ($symbol !== null) {
                $symbols[] = $symbol;
            } else {
                $this->warn("Skipped {$filename}: could not parse SVG");
            }
        }

        if (empty($symbols)) {
            $this->error('No valid SVG icons found.');

            return self::FAILURE;
        }

        $sprite = $this->buildSprite($symbols);
        $outputDir = dirname($outputPath);

        if (! is_dir($outputDir)) {
            File::makeDirectory($outputDir, 0755, true);
        }

        File::put($outputPath, $sprite);

        $this->info('Icon sprite built successfully.');
        $this->info("Output: {$outputPath}");
        $this->info('Icons: '.count($symbols));

        return self::SUCCESS;
    }

    private function svgToSymbol(string $content, string $id): ?string
    {
        $content = trim($content);

        if (! preg_match('/<svg\s([^>]*)>(.*)<\/svg>/s', $content, $matches)) {
            return null;
        }

        $attributes = $matches[1];
        $inner = $matches[2];

        $viewBox = $this->extractAttribute($attributes, 'viewBox') ?? '0 0 24 24';

        $symbolAttrs = [
            'id' => $id,
            'viewBox' => $viewBox,
        ];

        $inheritAttrs = ['fill', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin'];
        foreach ($inheritAttrs as $attr) {
            $value = $this->extractAttribute($attributes, $attr);
            if ($value !== null) {
                $symbolAttrs[$attr] = $value;
            }
        }

        $attrsString = $this->buildAttributes($symbolAttrs);
        $innerMinified = $this->minifySvgContent($inner);

        return "<symbol {$attrsString}>{$innerMinified}</symbol>";
    }

    private function extractAttribute(string $attributes, string $name): ?string
    {
        if (preg_match('/'.$name.'\s*=\s*["\']([^"\']*)["\']/i', $attributes, $m)) {
            return $m[1];
        }

        return null;
    }

    private function buildAttributes(array $attrs): string
    {
        $parts = [];
        foreach ($attrs as $key => $value) {
            $parts[] = $key.'="'.htmlspecialchars($value, ENT_QUOTES, 'UTF-8').'"';
        }

        return implode(' ', $parts);
    }

    private function minifySvgContent(string $content): string
    {
        $content = preg_replace('/<!--.*?-->/s', '', $content);
        $content = preg_replace('/\s+/', ' ', $content);

        return trim($content);
    }

    private function buildSprite(array $symbols): string
    {
        $svg  = '<?xml version="1.0" encoding="UTF-8"?>';
        $svg .= '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" style="display:none">';
        $svg .= implode('', $symbols);
        $svg .= '</svg>';

        return $svg;
    }
}
