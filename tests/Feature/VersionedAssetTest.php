<?php

namespace Tests\Feature;

use App\Models\Forum;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VersionedAssetTest extends TestCase
{
    use RefreshDatabase;

    public function test_versioned_asset_appends_content_hash(): void
    {
        $hash = substr(md5_file(public_path('assets/img/icons.svg')), 0, 8);

        $this->assertSame(asset('assets/img/icons.svg').'?v='.$hash, versioned_asset('assets/img/icons.svg'));
    }

    public function test_versioned_asset_without_file_returns_plain_url(): void
    {
        $this->assertSame(asset('assets/img/missing.svg'), versioned_asset('assets/img/missing.svg'));
    }

    public function test_icons_use_versioned_sprite_url(): void
    {
        Forum::factory()->create(['icon' => 'newspaper']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(versioned_asset('assets/img/icons.svg').'#newspaper', false);
    }
}
