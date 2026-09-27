<?php

namespace Database\Seeders;

use App\Actions\AcceptBid;
use App\Actions\CancelOrder;
use App\Actions\ConfirmCompletion;
use App\Actions\CreateOrder;
use App\Actions\DeliverOrder;
use App\Actions\LeaveReview;
use App\Actions\OpenDispute;
use App\Actions\PlaceBid;
use App\Actions\SendMessage;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

/**
 * Демо-биржа: заказчики, исполнители, заказы на аукционе и в работе.
 * Идёт через те же действия, что и сайт, поэтому данные согласованы. Повторно не запускается.
 *
 * Доступы (пароль у всех — password): admin@example.com, customer@example.com, executor@example.com.
 */
class DemoSeeder extends Seeder
{
    /** @var Collection<string, Category> */
    private Collection $tags;

    public function run(): void
    {
        if (User::query()->where('email', 'customer@example.com')->exists()) {
            $this->command?->info('Демо-данные уже есть — пропускаю.');

            return;
        }

        // Сидер не должен копить задачи рассылки в очереди: WebSocket тут не нужен.
        config(['broadcasting.default' => 'null', 'queue.default' => 'sync']);

        $this->tags = Category::query()->tags()->get()->keyBy('name');

        $this->user('Администратор', 'admin@example.com', UserRole::Admin);

        $customers = collect([
            $this->user('Анна Смирнова', 'customer@example.com', UserRole::Customer, '+7 900 111-22-33'),
            $this->user('Кофейня «Зерно»', 'coffee@example.com', UserRole::Customer),
            $this->user('Игорь Павлов', 'igor@example.com', UserRole::Customer),
        ]);

        $executors = collect([
            $this->executor('Дмитрий Ковалёв', 'executor@example.com',
                'Фулстек-разработчик, 4 года на Laravel и WordPress. Верстаю по макетам Figma пиксель-в-пиксель, делаю интеграции с CRM и платёжками. Сдаю с README и инструкцией.',
                ['Вёрстка (HTML/CSS/JS)', 'Laravel', 'WordPress', 'WooCommerce']),
            $this->executor('Мария Орлова', 'maria@example.com',
                'Веб-дизайнер. Лендинги, интернет-магазины, фирменный стиль. Работаю в Figma, отдаю макеты с UI-kit и адаптивом.',
                ['Веб-дизайн (Figma)', 'Логотипы', 'Баннеры и полиграфия']),
            $this->executor('Сергей Волков', 'sergey@example.com',
                'Backend на PHP с 2016 года. Laravel, очереди, REST API, оптимизация медленных запросов MySQL.',
                ['Laravel', 'Другие CMS']),
            $this->executor('Екатерина Лебедева', 'kate@example.com',
                'Копирайтер и SMM-специалист. Пишу продающие тексты для сайтов, веду соцсети малого бизнеса, составляю контент-планы.',
                ['Тексты для сайтов', 'SEO-тексты', 'Посты для соцсетей', 'SMM']),
            $this->executor('Артём Никитин', 'artem@example.com',
                'Верстальщик и WordPress-разработчик. Быстро, аккуратно, с адаптивом и оптимизацией скорости.',
                ['Вёрстка (HTML/CSS/JS)', 'WordPress', 'WooCommerce']),
            $this->executor('Олег Морозов', 'oleg@example.com',
                'Монтажёр и звукорежиссёр. Ролики для YouTube и рекламы, озвучка, чистка звука.',
                ['Монтаж видео', 'Озвучка', 'Обработка звука']),
            $this->executor('Полина Зайцева', 'polina@example.com',
                'Unity-разработчик и 2D-художник. Прототипы, механики, пиксель-арт и UI для игр.',
                ['Unity', '2D/3D-графика', 'Геймдизайн-документы']),
        ])->keyBy('email');

        $orders = [
            ['customer@example.com', 'Сверстать лендинг по макету в Figma', 'Нужна адаптивная вёрстка одностраничного лендинга (8 блоков) по готовому макету в Figma. Анимации при скролле, форма заявки с отправкой на почту. Макет и шрифты дам.', 15000, ['Вёрстка (HTML/CSS/JS)', 'Веб-дизайн (Figma)'],
                [['executor@example.com', 14000, 5], ['artem@example.com', 12000, 4], ['maria@example.com', 16000, 7]]],
            ['customer@example.com', 'Доработать интернет-магазин на WooCommerce', 'Магазин на WooCommerce: нужно подключить СДЭК, настроить фильтры товаров и ускорить загрузку каталога. Хостинг Beget.', 25000, ['WooCommerce', 'WordPress'],
                [['artem@example.com', 24000, 10], ['executor@example.com', 26000, 8]]],
            ['customer@example.com', 'Логотип и визитка для студии йоги', 'Нужен логотип (3 варианта на выбор) и макет визитки. Стиль — спокойный, природные цвета.', 8000, ['Логотипы', 'Баннеры и полиграфия'],
                [['maria@example.com', 8500, 6]]],
            ['customer@example.com', 'Тексты для 5 страниц сайта клининга', 'Главная, услуги, цены, о компании, контакты. Нужны продающие тексты с ключевыми словами.', 6000, ['Тексты для сайтов', 'SEO-тексты'], []],
            ['coffee@example.com', 'Вести Instagram и VK кофейни месяц', 'Контент-план, 12 постов и сторис, ответы в директ. Фото предоставим.', 20000, ['SMM', 'Посты для соцсетей'],
                [['kate@example.com', 19000, 30]]],
            ['coffee@example.com', 'Сайт-меню кофейни на Laravel с админкой', 'Простой сайт с меню, ценами и фотографиями, админка для изменения позиций. Нужен QR-код на столы.', 40000, ['Laravel'],
                [['sergey@example.com', 38000, 14], ['executor@example.com', 41000, 12]]],
            ['igor@example.com', 'Смонтировать ролик для YouTube (15 минут)', 'Есть исходники 2 часа, нужен динамичный монтаж, титры, музыка, цветокоррекция.', 7000, ['Монтаж видео'],
                [['oleg@example.com', 7500, 3]]],
            ['igor@example.com', 'Прототип 2D-платформера на Unity', 'Нужен прототип: управление, 2 уровня, враги, сбор монет. Графика — ассеты из стора.', 50000, ['Unity', 'Геймдизайн-документы'],
                [['polina@example.com', 48000, 21]]],
            ['igor@example.com', 'Перенести сайт с Tilda на WordPress', 'Сайт из 10 страниц, нужно перенести дизайн и контент, настроить формы.', 18000, ['WordPress', 'Вёрстка (HTML/CSS/JS)'], []],
        ];

        foreach ($orders as $index => [$customerEmail, $title, $description, $price, $tagNames, $bids]) {
            $customer = $customers->firstWhere('email', $customerEmail);
            $order = app(CreateOrder::class)->handle($customer, [
                'title' => $title,
                'description' => $description,
                'starting_price' => $price,
                'category_ids' => collect($tagNames)->map(fn (string $name) => $this->tags[$name]->id)->all(),
            ]);

            foreach ($bids as [$executorEmail, $offer, $days]) {
                app(PlaceBid::class)->handle($executors[$executorEmail], $order, [
                    'offer_price' => $offer,
                    'approach_description' => $this->approach($days),
                    'duration_days' => $days,
                ]);
            }

            // Разбросать даты, чтобы лента выглядела живой.
            $this->age($order, hours: (count($orders) - $index) * 7);
        }

        // Пара заказов уже в работе и один отменён — видно все статусы.
        $cafeSite = Order::query()->where('title', 'like', 'Сайт-меню%')->firstOrFail();
        $cafeChat = app(AcceptBid::class)->handle($cafeSite->customer()->firstOrFail(), $cafeSite->bids()->where('executor_id', $executors['sergey@example.com']->id)->firstOrFail());
        $this->chat($cafeChat, [
            ['customer', 'Здравствуйте! Выбрали вас. Когда сможете начать?'],
            ['executor', 'Добрый день! Могу сегодня. Пришлите, пожалуйста, меню и фото блюд.'],
            ['customer', 'Отправила на почту. QR-коды нужны на 12 столов.'],
            ['executor', 'Принято. Первую версию покажу через 5 дней.'],
        ]);

        // Кафе: исполнитель сдал работу, заказчица не согласна — спор ждёт администратора.
        app(DeliverOrder::class)->handle($executors['sergey@example.com'], $cafeSite);
        $this->chat($cafeChat, [
            ['executor', 'Готово: сайт на тестовом домене, QR-коды в архиве. Отметил работу сданной.'],
            ['customer', 'Админки для изменения цен нет, а она была в задаче. И QR-кодов 10, а не 12.'],
            ['executor', 'Админка не входила в сумму, это отдельная работа.'],
        ]);
        app(OpenDispute::class)->handle($cafeSite->customer()->firstOrFail(), $cafeSite,
            'В задаче явно указана админка для изменения позиций меню — её нет. QR-кодов 10 вместо 12. Исполнитель отказывается доделывать.');

        // Монтаж: сдан, принят и оценён.
        $video = Order::query()->where('title', 'like', 'Смонтировать%')->firstOrFail();
        $videoChat = app(AcceptBid::class)->handle($video->customer()->firstOrFail(), $video->bids()->firstOrFail());
        $this->completeWithReview($videoChat, 5, 'Отличный монтаж, уложился в срок, учёл все правки.');

        // История выполненных заказов — у исполнителей в карточках появляются рейтинг и отзывы.
        $history = [
            ['igor@example.com', 'executor@example.com', 'Вёрстка корпоративного сайта по макету', 30000, ['Вёрстка (HTML/CSS/JS)'], 5, 'Сделано аккуратно, адаптив идеальный. Рекомендую.'],
            ['coffee@example.com', 'executor@example.com', 'Интернет-магазин кофе на WooCommerce', 45000, ['WooCommerce'], 4, 'Всё работает, но сроки немного сдвинулись.'],
            ['igor@example.com', 'executor@example.com', 'Лендинг для онлайн-курса на Laravel', 20000, ['Laravel'], 5, null],
            ['coffee@example.com', 'artem@example.com', 'Ускорить сайт на WordPress', 9000, ['WordPress'], 4, 'Сайт стал грузиться заметно быстрее.'],
            ['igor@example.com', 'maria@example.com', 'Дизайн лендинга в Figma', 18000, ['Веб-дизайн (Figma)'], 5, 'Очень красиво и с UI-kit, как договаривались.'],
            ['coffee@example.com', 'kate@example.com', 'Тексты для сайта кофейни', 5000, ['Тексты для сайтов'], 3, 'Тексты хорошие, но пришлось дважды просить правки.'],
        ];

        foreach ($history as $index => [$customerEmail, $executorEmail, $title, $price, $tagNames, $rating, $comment]) {
            $order = app(CreateOrder::class)->handle($customers->firstWhere('email', $customerEmail), [
                'title' => $title,
                'description' => 'Выполненный заказ из истории демо-биржи.',
                'starting_price' => $price,
                'category_ids' => collect($tagNames)->map(fn (string $name) => $this->tags[$name]->id)->all(),
            ]);
            $bid = app(PlaceBid::class)->handle($executors[$executorEmail], $order, [
                'offer_price' => $price,
                'approach_description' => $this->approach(7),
                'duration_days' => 7,
            ]);
            $chat = app(AcceptBid::class)->handle($order->customer()->firstOrFail(), $bid);
            $this->completeWithReview($chat, $rating, $comment);
            $this->age($order, hours: 24 * (40 - $index * 5));
        }

        // Заказчик из демо-доступа тоже сразу видит чат: принимает Дмитрия по WooCommerce.
        $shop = Order::query()->where('title', 'like', 'Доработать интернет-магазин%')->firstOrFail();
        $shopChat = app(AcceptBid::class)->handle($shop->customer()->firstOrFail(), $shop->bids()->where('executor_id', $executors['executor@example.com']->id)->firstOrFail());
        $this->chat($shopChat, [
            ['customer', 'Дмитрий, добрый день! Доступы к хостингу пришлю в личном сообщении на почту.'],
            ['executor', 'Здравствуйте! Начну со СДЭК, потом фильтры и скорость. Вопрос: какие тарифы СДЭК показывать?'],
        ]);

        $tilda = Order::query()->where('title', 'like', 'Перенести сайт%')->firstOrFail();
        app(CancelOrder::class)->handle($tilda->customer()->firstOrFail(), $tilda);
    }

    private function user(string $name, string $email, UserRole $role, ?string $phone = null): User
    {
        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'role' => $role,
            'password' => Hash::make('password'),
        ]);
    }

    /** @param  list<string>  $tagNames */
    private function executor(string $name, string $email, string $description, array $tagNames): User
    {
        $user = $this->user($name, $email, UserRole::Executor);
        $user->executorProfile()->create(['description' => $description]);
        $user->categories()->attach(collect($tagNames)->map(fn (string $tag) => $this->tags[$tag]->id));

        return $user;
    }

    /** Сдать, принять и оценить заказ — как это сделали бы стороны на сайте. */
    private function completeWithReview(Conversation $conversation, int $rating, ?string $comment): void
    {
        $conversation->loadMissing(['order', 'customer', 'executor']);

        app(DeliverOrder::class)->handle($conversation->executor, $conversation->order);
        app(ConfirmCompletion::class)->handle($conversation->customer, $conversation->order);
        app(LeaveReview::class)->handle($conversation->customer, $conversation->order, $rating, $comment);
    }

    /** @param  list<array{0: 'customer'|'executor', 1: string}>  $lines */
    private function chat(Conversation $conversation, array $lines): void
    {
        $conversation->loadMissing(['customer', 'executor']);

        foreach ($lines as [$who, $text]) {
            app(SendMessage::class)->handle($who === 'customer' ? $conversation->customer : $conversation->executor, $conversation, $text);
        }
    }

    private function approach(int $days): string
    {
        return collect([
            'Изучу задачу и задам уточняющие вопросы в первый день.',
            'Делал похожие проекты — примеры покажу в чате.',
            'Разобью работу на этапы, промежуточный результат покажу через '.max(1, intdiv($days, 2)).' '.plural(max(1, intdiv($days, 2)), 'день', 'дня', 'дней').'.',
            'Правки в рамках задачи — бесплатно, сдам с инструкцией.',
        ])->shuffle()->take(3)->implode(' ');
    }

    private function age(Order $order, int $hours): void
    {
        $at = now()->subHours($hours);
        $order->forceFill(['created_at' => $at, 'updated_at' => $at])->saveQuietly();
        $order->bids()->update(['created_at' => $at->addHour(), 'updated_at' => $at->addHour()]);
    }
}
