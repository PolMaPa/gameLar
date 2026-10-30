<?php

namespace Tests\Feature;

use App\Models\Game;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\GameSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_seeder_creates_an_admin_and_is_idempotent(): void
    {
        $this->seed(AdminUserSeeder::class);
        $this->seed(AdminUserSeeder::class);

        $admins = User::query()->where('is_admin', true)->get();

        $this->assertCount(1, $admins);
        $this->assertSame('admin@gamehub.test', $admins->first()->email);
        $this->assertTrue($admins->first()->isAdmin());
    }

    public function test_admin_seeder_promotes_an_existing_user_without_duplicating(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@gamehub.test',
            'is_admin' => false,
        ]);

        $this->seed(AdminUserSeeder::class);

        $this->assertSame(1, User::query()->where('email', 'admin@gamehub.test')->count());
        $this->assertTrue($user->fresh()->isAdmin());
    }

    public function test_game_seeder_creates_at_least_ten_games_and_is_idempotent(): void
    {
        $this->seed(GameSeeder::class);
        $this->seed(GameSeeder::class);

        $this->assertGreaterThanOrEqual(10, Game::count());
        $this->assertSame(12, Game::count());
    }

    public function test_seeded_games_point_to_existing_local_posters(): void
    {
        $this->seed(GameSeeder::class);

        $withImage = Game::query()->whereNotNull('image_url')->get();

        $this->assertGreaterThanOrEqual(10, $withImage->count());

        foreach ($withImage as $game) {
            $this->assertStringStartsWith('/images/games/', $game->image_url);
            $this->assertFileExists(public_path(ltrim($game->image_url, '/')));
        }
    }
}
