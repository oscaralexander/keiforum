<?php

use App\Lib\Image;
use App\Models\Forum;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Forum::query()->where('slug', config('news.forum_slug'))->exists()) {
            (new Forum)->forceFill([
                'description' => 'Praat mee over het laatste Amersfoortse nieuws.',
                'icon' => 'newspaper',
                'name' => 'Nieuws',
                'slug' => config('news.forum_slug'),
            ])->save();
        }

        if (! User::query()->where('username', config('news.username'))->exists()) {
            (new User)->forceFill([
                'email' => 'nieuwsplein33@keiforum.nl',
                'email_verified_at' => now(),
                'name' => 'Nieuwsplein33',
                'password' => null,
                'username' => config('news.username'),
            ])->save();
        }

        $user = User::query()->where('username', config('news.username'))->firstOrFail();

        if (! $user->has_avatar) {
            $avatarContents = (new Image)
                ->read(resource_path('img/nieuwsplein33.png'))
                ->resize(1024)
                ->encode(80);

            if (Storage::disk('public')->put($user->avatar, $avatarContents)) {
                $user->forceFill(['has_avatar' => true])->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
