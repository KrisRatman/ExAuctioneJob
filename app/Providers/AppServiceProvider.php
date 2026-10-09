<?php

namespace App\Providers;

use App\Support\Ai\DisabledLanguageModel;
use App\Support\Ai\GigaChat;
use App\Support\Ai\LanguageModel;
use App\Support\Ai\YandexGpt;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // ИИ-провайдер для поиска по смыслу и помощника; без драйвера ИИ-функции скрыты.
        $this->app->singleton(LanguageModel::class, fn () => match (config('ideajob.ai.driver')) {
            'yandexgpt' => new YandexGpt(config('services.yandexgpt')),
            'gigachat' => new GigaChat(config('services.gigachat')),
            default => new DisabledLanguageModel,
        });
    }

    public function boot(): void
    {
        Date::use(CarbonImmutable::class);
        CarbonImmutable::setLocale('ru');

        // Поле не в #[Fillable] — ошибка при разработке, а не тихая потеря данных.
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());
        Model::preventLazyLoading(! $this->app->isProduction());

        // За HTTPS-прокси ссылки должны строиться от APP_URL.
        $appUrl = (string) config('app.url');

        if (str_starts_with($appUrl, 'https://')) {
            URL::forceRootUrl($appUrl);
            URL::forceScheme('https');
            Paginator::currentPathResolver(fn () => url(request()->path()));
        }
    }
}
