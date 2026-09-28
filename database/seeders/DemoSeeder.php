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
use App\Models\City;
use App\Models\Conversation;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;

/**
 * Демо-биржа: заказчики, мастера в Екатеринбурге и Москве, заказы на аукционе и в работе.
 * Идёт через те же действия, что и сайт, поэтому данные согласованы. Повторно не запускается.
 *
 * Доступы (пароль у всех — password): admin@example.com, customer@example.com, executor@example.com.
 */
class DemoSeeder extends Seeder
{
    /** @var Collection<string, Category> */
    private Collection $tags;

    /** @var Collection<string, City> */
    private Collection $cities;

    public function run(): void
    {
        if (User::query()->where('email', 'customer@example.com')->exists()) {
            $this->command?->info('Демо-данные уже есть — пропускаю.');

            return;
        }

        // Сидер не должен копить задачи рассылки в очереди: WebSocket тут не нужен.
        config(['broadcasting.default' => 'null', 'queue.default' => 'sync']);

        $this->tags = Category::query()->tags()->get()->keyBy('name');
        $this->cities = City::query()->get()->keyBy('name');

        $this->user('Администратор', 'admin@example.com', UserRole::Admin);

        $customers = collect([
            $this->user('Анна Смирнова', 'customer@example.com', UserRole::Customer, '+7 900 111-22-33'),
            $this->user('Кофейня «Зерно»', 'coffee@example.com', UserRole::Customer),
            $this->user('Игорь Павлов', 'igor@example.com', UserRole::Customer),
        ]);

        $executors = collect([
            $this->executor('Дмитрий Ковалёв', 'executor@example.com', 'Екатеринбург',
                'Мастер-отделочник, 8 лет в ремонте. Плитка и керамогранит, выравнивание и покраска стен, ванные комнаты под ключ. Свой инструмент, убираю за собой, гарантия на работу — год.',
                ['Укладка плитки', 'Штукатурка и шпаклёвка', 'Малярные работы', 'Напольные покрытия', 'Ремонт под ключ']),
            $this->executor('Артём Никитин', 'artem@example.com', 'Екатеринбург',
                'Плиточник и мастер по полам. Плитка, ламинат, кварцвинил, натяжные потолки. Работаю аккуратно и в срок.',
                ['Укладка плитки', 'Напольные покрытия', 'Натяжные потолки']),
            $this->executor('Сергей Волков', 'sergey@example.com', 'Екатеринбург',
                'Ремонт холодильников и бытовой техники с 2012 года, в том числе торгового холодильного оборудования. Выезд в день обращения, диагностика бесплатно при ремонте.',
                ['Холодильники', 'Стиральные машины', 'Посудомоечные машины', 'Плиты и духовки', 'Кондиционеры']),
            $this->executor('Олег Морозов', 'oleg@example.com', 'Екатеринбург',
                'Сантехник. Установка смесителей, унитазов, ванн и бойлеров, устранение засоров и протечек, разводка труб.',
                ['Установка сантехники', 'Засоры и протечки', 'Отопление и водонагреватели']),
            $this->executor('Мария Орлова', 'maria@example.com', 'Екатеринбург',
                'Клининг квартир и офисов: поддерживающая и генеральная уборка, уборка после ремонта, химчистка диванов и ковров. Своя химия и оборудование.',
                ['Уборка квартир', 'Уборка после ремонта', 'Химчистка мебели и ковров']),
            $this->executor('Павел Зайцев', 'pavel@example.com', 'Екатеринбург',
                'Электрик и мастер на час. Розетки, выключатели, люстры, щитки; повешу полки, карнизы, телевизор, соберу мелкую мебель.',
                ['Электромонтаж', 'Розетки и светильники', 'Мелкий бытовой ремонт', 'Навеска полок и карнизов']),
            $this->executor('Андрей Лебедев', 'andrey@example.com', 'Москва',
                'Сборщик мебели: кухни, шкафы-купе, IKEA и мебель на заказ. Подниму на этаж, вывезу упаковку.',
                ['Сборка мебели', 'Ремонт мебели', 'Мебель на заказ', 'Грузчики']),
        ])->keyBy('email');

        $orders = [
            ['customer@example.com', 'Екатеринбург', 'Уложить плитку в ванной, 6 м²', 'Стены и пол в ванной, плитка 30×60 уже куплена. Старую плитку сняли, стены ровные. Нужна затирка и установка ревизионного люка. Район — Академический.', 45000, ['Укладка плитки'],
                [['executor@example.com', 42000, 5], ['artem@example.com', 38000, 4]]],
            ['customer@example.com', 'Екатеринбург', 'Выровнять и покрасить стены в спальне', 'Комната 14 м², стены после старых обоев, есть трещины. Нужно зашпаклевать, отшлифовать и покрасить в два слоя. Краску купим сами.', 25000, ['Малярные работы', 'Штукатурка и шпаклёвка'],
                [['executor@example.com', 26000, 6]]],
            ['igor@example.com', 'Екатеринбург', 'Ремонт ванной комнаты под ключ', 'Санузел совмещённый, 4 м². Демонтаж старой плитки, разводка труб, плитка на стены и пол, установка ванны и унитаза. Материалы закупаем вместе.', 150000, ['Ремонт под ключ', 'Укладка плитки'], []],
            ['coffee@example.com', 'Екатеринбург', 'Постелить кварцвинил в зале кофейни, 35 м²', 'Основание — ровная стяжка. Кварцвинил замковый, куплен. Работать можно только ночью или в понедельник, когда кофейня закрыта.', 21000, ['Напольные покрытия'],
                [['artem@example.com', 20000, 2]]],
            ['igor@example.com', 'Екатеринбург', 'Покрасить потолок и откосы на кухне', 'Потолок 9 м² и два оконных откоса: подготовить, зашпаклевать трещины, покрасить белой матовой краской.', 7000, ['Малярные работы'], []],
            ['customer@example.com', 'Екатеринбург', 'Не морозит холодильник Indesit', 'Двухкамерный Indesit, лет 7. Морозилка работает, в холодильной камере +12. Компрессор включается. Нужна диагностика и ремонт.', 3000, ['Холодильники'],
                [['sergey@example.com', 3500, 1]]],
            ['customer@example.com', 'Екатеринбург', 'Генеральная уборка после ремонта, 2 комнаты', 'Квартира 54 м²: строительная пыль, следы краски на окнах и полу, помыть кухню и санузел.', 8000, ['Уборка после ремонта', 'Уборка квартир'], []],
            ['coffee@example.com', 'Екатеринбург', 'Заменить смеситель и сифон на кухне кофейни', 'Течёт смеситель, сифон под мойкой подтекает. Новый смеситель купим, нужен мастер утром до открытия (до 8:00).', 4000, ['Установка сантехники', 'Засоры и протечки'],
                [['oleg@example.com', 4500, 1]]],
            ['coffee@example.com', 'Екатеринбург', 'Починить витринный холодильник в кофейне', 'Витрина для десертов перестала охлаждать ночью, внутри +15. Нужен мастер с опытом торгового оборудования.', 12000, ['Холодильники'],
                [['sergey@example.com', 11000, 2]]],
            ['igor@example.com', 'Екатеринбург', 'Повесить люстру и 4 полки', 'Люстра на крюк (потолок бетонный), 4 полки на гипсокартон. Крепёж есть.', 3000, ['Розетки и светильники', 'Навеска полок и карнизов'],
                [['pavel@example.com', 3000, 1]]],
            ['igor@example.com', 'Москва', 'Собрать шкаф-купе и кухню IKEA', 'Шкаф-купе 2 м и кухня 3 м (METOD), всё привезено. Нужна сборка и навеска шкафов, врезка мойки.', 15000, ['Сборка мебели'],
                [['andrey@example.com', 14000, 2]]],
            ['igor@example.com', 'Екатеринбург', 'Поменять личинку замка входной двери', 'Потеряли ключ, нужно заменить личинку. Замок Mottura.', 2000, ['Замки и двери'], []],
        ];

        foreach ($orders as $index => [$customerEmail, $cityName, $title, $description, $price, $tagNames, $bids]) {
            $customer = $customers->firstWhere('email', $customerEmail);
            $order = app(CreateOrder::class)->handle($customer, [
                'city_id' => $this->cities[$cityName]->id,
                'title' => $title,
                'description' => $description,
                'starting_price' => $price,
                'category_ids' => collect($tagNames)->map(fn (string $name) => $this->tags[$name]->id)->all(),
            ]);

            foreach ($bids as [$executorEmail, $offer, $days]) {
                app(PlaceBid::class)->handle($executors[$executorEmail], $order, [
                    'offer_price' => $offer,
                    'approach_description' => $this->approach(),
                    'duration_days' => $days,
                ]);
            }

            // Разбросать даты, чтобы лента выглядела живой.
            $this->age($order, hours: (count($orders) - $index) * 7);
        }

        // Витрина в кофейне: мастер отремонтировал, но заказчица не согласна — спор ждёт администратора.
        $cafeFridge = Order::query()->where('title', 'like', 'Починить витринный%')->firstOrFail();
        $cafeChat = app(AcceptBid::class)->handle($cafeFridge->customer()->firstOrFail(), $cafeFridge->bids()->where('executor_id', $executors['sergey@example.com']->id)->firstOrFail());
        $this->chat($cafeChat, [
            ['customer', 'Здравствуйте! Выбрали вас. Когда сможете приехать?'],
            ['executor', 'Добрый день! Сегодня после 18:00. Напишите, пожалуйста, модель витрины.'],
            ['customer', 'Carboma, ей три года. Вход со двора, адрес пришлю отдельным сообщением.'],
            ['executor', 'Понял, возьму фреон и пусковое реле на всякий случай.'],
        ]);

        app(DeliverOrder::class)->handle($executors['sergey@example.com'], $cafeFridge);
        $this->chat($cafeChat, [
            ['executor', 'Заменил реле и дозаправил фреон, витрина держит +4. Отметил работу сданной.'],
            ['customer', 'Через два дня витрина снова греется, десерты пришлось списать.'],
            ['executor', 'Это уже другая неисправность — компрессор. Его замена в сумму не входила.'],
        ]);
        app(OpenDispute::class)->handle($cafeFridge->customer()->firstOrFail(), $cafeFridge,
            'Мастер взял деньги за ремонт, а через два дня витрина снова перестала охлаждать. Приехать повторно без доплаты отказывается.');

        // Люстра и полки: сдано, принято и оценено.
        $shelves = Order::query()->where('title', 'like', 'Повесить люстру%')->firstOrFail();
        $shelvesChat = app(AcceptBid::class)->handle($shelves->customer()->firstOrFail(), $shelves->bids()->firstOrFail());
        $this->completeWithReview($shelvesChat, 5, 'Приехал вовремя, всё ровно по уровню, убрал пыль за собой.');

        // История выполненных заказов — у исполнителей в карточках появляются рейтинг и отзывы.
        $history = [
            ['igor@example.com', 'executor@example.com', 'Плитка на кухонный фартук', 12000, ['Укладка плитки'], 5, 'Ровно, швы аккуратные, мусор вынес сам. Рекомендую.'],
            ['coffee@example.com', 'executor@example.com', 'Отделка санузла в кофейне под ключ', 60000, ['Ремонт под ключ'], 4, 'Сделано хорошо, но закончил на два дня позже.'],
            ['igor@example.com', 'executor@example.com', 'Шпаклёвка и покраска потолка', 9000, ['Малярные работы'], 5, null],
            ['coffee@example.com', 'artem@example.com', 'Уложить ламинат в зале, 40 м²', 16000, ['Напольные покрытия'], 4, 'Быстро и аккуратно, пороги поставил.'],
            ['igor@example.com', 'maria@example.com', 'Уборка квартиры после ремонта', 7000, ['Уборка после ремонта'], 5, 'Отмыли всё, даже окна и плитку от затирки.'],
            ['coffee@example.com', 'oleg@example.com', 'Прочистить засор в кофейне', 2500, ['Засоры и протечки'], 3, 'Засор убрал, но приехал на час позже договорённого.'],
        ];

        foreach ($history as $index => [$customerEmail, $executorEmail, $title, $price, $tagNames, $rating, $comment]) {
            $order = app(CreateOrder::class)->handle($customers->firstWhere('email', $customerEmail), [
                'city_id' => $this->cities['Екатеринбург']->id,
                'title' => $title,
                'description' => 'Выполненный заказ из истории демо-биржи.',
                'starting_price' => $price,
                'category_ids' => collect($tagNames)->map(fn (string $name) => $this->tags[$name]->id)->all(),
            ]);
            $bid = app(PlaceBid::class)->handle($executors[$executorEmail], $order, [
                'offer_price' => $price,
                'approach_description' => $this->approach(),
                'duration_days' => 2,
            ]);
            $chat = app(AcceptBid::class)->handle($order->customer()->firstOrFail(), $bid);
            $this->completeWithReview($chat, $rating, $comment);
            $this->age($order, hours: 24 * (40 - $index * 5));
        }

        // Заказчик из демо-доступа тоже сразу видит чат: принимает Дмитрия на покраску стен.
        $walls = Order::query()->where('title', 'like', 'Выровнять и покрасить%')->firstOrFail();
        $wallsChat = app(AcceptBid::class)->handle($walls->customer()->firstOrFail(), $walls->bids()->where('executor_id', $executors['executor@example.com']->id)->firstOrFail());
        $this->chat($wallsChat, [
            ['customer', 'Дмитрий, добрый день! Удобно начать в субботу? Адрес пришлю отдельным сообщением.'],
            ['executor', 'Здравствуйте! Да, в субботу в 10:00. Краску купите сами или взять по чеку? Нужно около 10 литров.'],
        ]);

        $lock = Order::query()->where('title', 'like', 'Поменять личинку%')->firstOrFail();
        app(CancelOrder::class)->handle($lock->customer()->firstOrFail(), $lock);
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
    private function executor(string $name, string $email, string $cityName, string $description, array $tagNames): User
    {
        $user = $this->user($name, $email, UserRole::Executor);
        $user->executorProfile()->create(['city_id' => $this->cities[$cityName]->id, 'description' => $description]);
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

    private function approach(): string
    {
        return collect([
            'Приеду, осмотрю и подтвержу цену на месте — если объём совпадает с описанием, начну сразу.',
            'Делал много похожих работ — фото покажу в чате.',
            'Свой инструмент, расходники могу закупить сам по чекам.',
            'Даю гарантию на работу, после себя убираю.',
        ])->shuffle()->take(3)->implode(' ');
    }

    private function age(Order $order, int $hours): void
    {
        $at = now()->subHours($hours);
        $order->forceFill(['created_at' => $at, 'updated_at' => $at])->saveQuietly();
        $order->bids()->update(['created_at' => $at->addHour(), 'updated_at' => $at->addHour()]);
    }
}
