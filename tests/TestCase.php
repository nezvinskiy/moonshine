<?php

declare(strict_types=1);

namespace MoonShine\Tests;

use Arr;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;
use MoonShine\Menu\MenuItem;
use MoonShine\Models\MoonshineUser;
use MoonShine\Models\MoonshineUserRole;
use MoonShine\MoonShine;
use MoonShine\Providers\MoonShineServiceProvider;
use MoonShine\Resources\MoonShineUserResource;
use MoonShine\Resources\Resource;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use InteractsWithViews;

    protected Authenticatable|MoonshineUser $adminUser;

    protected Resource $moonShineUserResource;

    protected function setUp(): void
    {
        parent::setUp();

        $this->performApplication()
            ->resolveFactories()
            ->resolveSuperUser()
            ->resolveMoonShineUserResource()
            ->registerTestResource();
    }

    protected function performApplication(): static
    {
        config(
            Arr::dot(config('moonshine.auth', []), 'auth.')
        );

        $this->artisan('moonshine:install');

        $this->artisan('config:clear');
        $this->artisan('view:clear');
        $this->artisan('cache:clear');

        $this->refreshApplication();
        $this->loadLaravelMigrations();
        $this->loadMigrationsFrom(realpath('./database/migrations'));

        return $this;
    }

    protected function resolveFactories(): static
    {
        Factory::guessFactoryNamesUsing(static function ($factory) {
            $factoryBasename = class_basename($factory);

            return "MoonShine\Database\Factories\\$factoryBasename".'Factory';
        });

        return $this;
    }

    protected function superAdminAttributes(): array
    {
        return [
            'moonshine_user_role_id' => MoonshineUserRole::DEFAULT_ROLE_ID,
            'name' => fake()->name(),
            'email' => fake()->email(),
            'password' => bcrypt('test'),
        ];
    }

    protected function resolveSuperUser(): static
    {
        $this->adminUser = MoonshineUser::factory()
            ->create($this->superAdminAttributes())
            ->load('moonshineUserRole');

        return $this;
    }

    protected function resolveMoonShineUserResource(): static
    {
        $this->moonShineUserResource = new MoonShineUserResource();

        return $this;
    }

    protected function registerTestResource(): static
    {
        MoonShine::resources([
            $this->moonShineUserResource(),
        ]);

        MoonShine::menu([
            MenuItem::make('Admins', $this->moonShineUserResource()),
        ]);

        return $this;
    }

    protected function moonShineUserResource(): Resource
    {
        return $this->moonShineUserResource;
    }

    protected function adminUser(): Model|Builder|Authenticatable
    {
        return $this->adminUser;
    }

    protected function asAdmin(): TestCase
    {
        return $this->actingAs($this->adminUser(), 'moonshine');
    }

    protected function getPackageProviders($app): array
    {
        return [
            MoonShineServiceProvider::class,
        ];
    }
}
