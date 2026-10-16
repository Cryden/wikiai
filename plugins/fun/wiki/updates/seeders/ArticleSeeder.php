<?php declare(strict_types=1);

namespace Fun\Wiki\Updates\Seeders;

use October\Rain\Database\Updates\Seeder;
use Fun\Wiki\Models\Article;
use Fun\Wiki\Models\Template;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $templates = Template::all()->keyBy('code');

        Article::query()->delete();

        $tplUniverse    = $templates->get('universe')?->id ?? 79;
        $tplBookSeries  = $templates->get('bookSeries')?->id ?? 81;
        $tplBook        = $templates->get('book')?->id ?? 82;
        $tplCharacter   = $templates->get('character')?->id ?? 87;
        $tplPerson      = $templates->get('person')?->id ?? 77;
        $tplCompany     = $templates->get('company')?->id ?? 92;
        $tplGenre       = $templates->get('genre')?->id ?? 98;
        $tplNewsSeries  = $templates->get('newsSeries')?->id ?? 89;
        $tplNews        = $templates->get('new')?->id ?? 90;

        // 1. Вселенные (4)
        $lotr = Article::updateOrCreate(
            ['slug' => 'lotr'],
            [
                'title'       => 'Легендариум Толкина (Властелин Колец)',
                'template_id' => $tplUniverse,
                'status'      => 'published',
                'data'        => [
                    'name_ru' => 'Властелин Колец',
                    'since'   => '1937-09-21',
                ],
                'content'     => 'Вымышленная вселенная легендариума Дж. Р. Р. Толкина, включающая Арду и континент Средиземье.',
            ]
        );

        $dune = Article::updateOrCreate(
            ['slug' => 'dune'],
            [
                'title'       => 'Вселенная Дюны',
                'template_id' => $tplUniverse,
                'status'      => 'published',
                'data'        => [
                    'name_ru' => 'Дюна',
                    'since'   => '1965-08-01',
                ],
                'content'     => 'Научно-фантастическая вселенная, созданная Фрэнком Гербертом. Центр галактической феодальной империи — пустынная планета Арракис.',
            ]
        );

        $hp = Article::updateOrCreate(
            ['slug' => 'harry-potter'],
            [
                'title'       => 'Волшебный мир Гарри Поттера',
                'template_id' => $tplUniverse,
                'status'      => 'published',
                'data'        => [
                    'name_ru' => 'Гарри Поттер',
                    'since'   => '1997-06-26',
                ],
                'content'     => 'Фэнтезийная медиафраншиза и вымышленная вселенная, мир магов и маглов, созданный Дж. К. Роулинг.',
            ]
        );

        $cyberpunk = Article::updateOrCreate(
            ['slug' => 'cyberpunk'],
            [
                'title'       => 'Вселенная Киберпанк (Найт-Сити)',
                'template_id' => $tplUniverse,
                'status'      => 'published',
                'data'        => [
                    'name_ru' => 'Киберпанк',
                    'since'   => '1988-11-15',
                ],
                'content'     => 'Мрачный мир антиутопии будущего, мегакорпораций, киберимплантов и уличных наёмников.',
            ]
        );

        // 2. Циклы книг (3)
        $lotrSeries = Article::updateOrCreate(
            ['slug' => 'lotr-trilogy'],
            [
                'title'       => 'Трилогия «Властелин Колец»',
                'template_id' => $tplBookSeries,
                'parent_id'   => $lotr->id,
                'status'      => 'published',
                'data'        => ['is_anthology' => false],
                'content'     => 'Главный роман-эпопея Дж. Р. Р. Толкина в жанре высокой эпической фантастики.',
            ]
        );

        $duneSeries = Article::updateOrCreate(
            ['slug' => 'dune-chronicles'],
            [
                'title'       => 'Хроники Дюны',
                'template_id' => $tplBookSeries,
                'parent_id'   => $dune->id,
                'status'      => 'published',
                'data'        => ['is_anthology' => false],
                'content'     => 'Оригинальная гексалогия романов Фрэнка Герберта о судьбе семьи Атрейдесов.',
            ]
        );

        $hpSeries = Article::updateOrCreate(
            ['slug' => 'hp-series'],
            [
                'title'       => 'Серия романов о Гарри Поттере',
                'template_id' => $tplBookSeries,
                'parent_id'   => $hp->id,
                'status'      => 'published',
                'data'        => ['is_anthology' => false],
                'content'     => 'Семь ключевых книг о взрослении Гарри Поттера в школе чародейства и волшебства Хогвартс.',
            ]
        );

        // 3. Книги (12)
        $booksData = [
            // LOTR
            ['title' => 'Братство Кольца', 'slug' => 'the-fellowship-of-the-ring', 'parent_id' => $lotrSeries->id, 'year' => '1954', 'lang' => 'Английский', 'type' => 'Роман'],
            ['title' => 'Две крепости', 'slug' => 'the-two-towers', 'parent_id' => $lotrSeries->id, 'year' => '1954', 'lang' => 'Английский', 'type' => 'Роман'],
            ['title' => 'Возвращение короля', 'slug' => 'the-return-of-the-king', 'parent_id' => $lotrSeries->id, 'year' => '1955', 'lang' => 'Английский', 'type' => 'Роман'],
            ['title' => 'Хоббит, или Туда и обратно', 'slug' => 'the-hobbit', 'parent_id' => $lotr->id, 'year' => '1937', 'lang' => 'Английский', 'type' => 'Повесть'],
            // Dune
            ['title' => 'Дюна', 'slug' => 'dune-novel', 'parent_id' => $duneSeries->id, 'year' => '1965', 'lang' => 'Английский', 'type' => 'Роман'],
            ['title' => 'Мессия Дюны', 'slug' => 'dune-messiah', 'parent_id' => $duneSeries->id, 'year' => '1969', 'lang' => 'Английский', 'type' => 'Роман'],
            ['title' => 'Дети Дюны', 'slug' => 'children-of-dune', 'parent_id' => $duneSeries->id, 'year' => '1976', 'lang' => 'Английский', 'type' => 'Роман'],
            ['title' => 'Бог-император Дюны', 'slug' => 'god-emperor-of-dune', 'parent_id' => $duneSeries->id, 'year' => '1981', 'lang' => 'Английский', 'type' => 'Роман'],
            // HP
            ['title' => 'Гарри Поттер и философский камень', 'slug' => 'hp-stone', 'parent_id' => $hpSeries->id, 'year' => '1997', 'lang' => 'Английский', 'type' => 'Роман'],
            ['title' => 'Гарри Поттер и Тайная комната', 'slug' => 'hp-chamber', 'parent_id' => $hpSeries->id, 'year' => '1998', 'lang' => 'Английский', 'type' => 'Роман'],
            ['title' => 'Гарри Поттер и узник Азкабана', 'slug' => 'hp-prisoner', 'parent_id' => $hpSeries->id, 'year' => '1999', 'lang' => 'Английский', 'type' => 'Роман'],
            ['title' => 'Гарри Поттер и Кубок огня', 'slug' => 'hp-goblet', 'parent_id' => $hpSeries->id, 'year' => '2000', 'lang' => 'Английский', 'type' => 'Роман'],
        ];

        foreach ($booksData as $b) {
            Article::updateOrCreate(
                ['slug' => $b['slug']],
                [
                    'title'       => $b['title'],
                    'template_id' => $tplBook,
                    'parent_id'   => $b['parent_id'],
                    'status'      => 'published',
                    'data'        => [
                        'created_data' => $b['year'] . '-01-01',
                        'language'     => $b['lang'],
                        'type'         => $b['type'],
                    ],
                    'content'     => "Литературное произведение «{$b['title']}», впервые опубликованное в {$b['year']} году.",
                ]
            );
        }

        // 4. Персонажи (16)
        $charactersData = [
            // LOTR
            ['title' => 'Фродо Бэггинс', 'slug' => 'frodo-baggins', 'parent_id' => $lotr->id, 'desc' => 'Хранитель Единого Кольца, хоббит из Шира.'],
            ['title' => 'Гэндальф Серый', 'slug' => 'gandalf', 'parent_id' => $lotr->id, 'desc' => 'Могущественный маг Истари, духовный наставник Братства Кольца.'],
            ['title' => 'Арагорн', 'slug' => 'aragorn', 'parent_id' => $lotr->id, 'desc' => 'Следопыт Севера, законный наследник трона Гондора и Арнора.'],
            ['title' => 'Леголас', 'slug' => 'legolas', 'parent_id' => $lotr->id, 'desc' => 'Эльфийский принц Лихолесья, меткий лучник.'],
            ['title' => 'Гимли', 'slug' => 'gimli', 'parent_id' => $lotr->id, 'desc' => 'Гном из рода Дурина, верный соратник Леголаса и Арагорна.'],
            ['title' => 'Саурон', 'slug' => 'sauron', 'parent_id' => $lotr->id, 'desc' => 'Тёмный Властелин Мордора, создатель Кольца Всевластья.'],
            // Dune
            ['title' => 'Пол Атрейдес (Муад’Диб)', 'slug' => 'paul-atreides', 'parent_id' => $dune->id, 'desc' => 'Квисатц Хадерах, мессия фрименов и император известной Вселенной.'],
            ['title' => 'Леди Джессика', 'slug' => 'lady-jessica', 'parent_id' => $dune->id, 'desc' => 'Наложница герцога Лето Атрейдеса, воспитанница ордена Бене Гессерит.'],
            ['title' => 'Барон Владимир Харконнен', 'slug' => 'vladimir-harkonnen', 'parent_id' => $dune->id, 'desc' => 'Глава дома Харконненов, заклятый враг дома Атрейдес.'],
            ['title' => 'Чани', 'slug' => 'chani', 'parent_id' => $dune->id, 'desc' => 'Фрименская воительница, возлюбленная Пола Атрейдеса.'],
            ['title' => 'Дункан Айдахо', 'slug' => 'duncan-idaho', 'parent_id' => $dune->id, 'desc' => 'Мастер меча дома Атрейдес, легендарный воин и гхола.'],
            // HP
            ['title' => 'Гарри Поттер', 'slug' => 'harry-potter-char', 'parent_id' => $hp->id, 'desc' => 'Мальчик, который выжил. Главный герой саги о волшебниках.'],
            ['title' => 'Гермиона Грейнджер', 'slug' => 'hermione-granger', 'parent_id' => $hp->id, 'desc' => 'Самая одаренная ученица Хогвартса своего поколения.'],
            ['title' => 'Рон Уизли', 'slug' => 'ron-weasley', 'parent_id' => $hp->id, 'desc' => 'Младший сын семейства Уизли, верный друг Гарри Поттера.'],
            ['title' => 'Альбус Дамблдор', 'slug' => 'albus-dumbledore', 'parent_id' => $hp->id, 'desc' => 'Величайший волшебник современности, директор школы Хогвартс.'],
            ['title' => 'Северус Снейп', 'slug' => 'severus-snape', 'parent_id' => $hp->id, 'desc' => 'Мастер зельеварения, декан Слизерина и двойной агент.'],
        ];

        foreach ($charactersData as $c) {
            Article::updateOrCreate(
                ['slug' => $c['slug']],
                [
                    'title'       => $c['title'],
                    'template_id' => $tplCharacter,
                    'parent_id'   => $c['parent_id'],
                    'status'      => 'published',
                    'data'        => [],
                    'content'     => $c['desc'],
                ]
            );
        }

        // 5. Персоны (8)
        $personsData = [
            ['title' => 'Джон Рональд Руэл Толкин', 'slug' => 'jrr-tolkien', 'orig' => 'J. R. R. Tolkien', 'country' => 'Великобритания', 'birth' => '1892-01-03', 'role' => 'Писатель, лингвист'],
            ['title' => 'Фрэнк Герберт', 'slug' => 'frank-herbert', 'orig' => 'Frank Herbert', 'country' => 'США', 'birth' => '1920-10-08', 'role' => 'Писатель-фантаст'],
            ['title' => 'Джоан Роулинг', 'slug' => 'jk-rowling', 'orig' => 'J. K. Rowling', 'country' => 'Великобритания', 'birth' => '1965-07-31', 'role' => 'Писательница, сценарист'],
            ['title' => 'Майк Пондсмит', 'slug' => 'mike-pondsmith', 'orig' => 'Mike Pondsmith', 'country' => 'США', 'birth' => '1954-04-14', 'role' => 'Геймдизайнер, сценарист'],
            ['title' => 'Айзек Азимов', 'slug' => 'isaac-asimov', 'orig' => 'Isaac Asimov', 'country' => 'США', 'birth' => '1920-01-02', 'role' => 'Писатель, биохимик'],
            ['title' => 'Филип Киндред Дик', 'slug' => 'philip-k-dick', 'orig' => 'Philip K. Dick', 'country' => 'США', 'birth' => '1928-12-16', 'role' => 'Писатель-фантаст'],
            ['title' => 'Артур Чарльз Кларк', 'slug' => 'arthur-c-clarke', 'orig' => 'Arthur C. Clarke', 'country' => 'Великобритания', 'birth' => '1917-12-16', 'role' => 'Писатель, ученый'],
            ['title' => 'Станислав Лем', 'slug' => 'stanislaw-lem', 'orig' => 'Stanisław Lem', 'country' => 'Польша', 'birth' => '1921-09-12', 'role' => 'Писатель, философ, футуролог'],
        ];

        foreach ($personsData as $p) {
            Article::updateOrCreate(
                ['slug' => $p['slug']],
                [
                    'title'       => $p['title'],
                    'template_id' => $tplPerson,
                    'status'      => 'published',
                    'data'        => [
                        'original_name' => $p['orig'],
                        'full_name'     => $p['title'],
                        'birth_place'   => $p['country'],
                        'birth_date'    => $p['birth'],
                        'roles'         => $p['role'],
                    ],
                    'content'     => "Знаменитый автор {$p['title']} ({$p['orig']}). Родился в стране: {$p['country']}.",
                ]
            );
        }

        // 6. Организации (4)
        $companiesData = [
            ['title' => 'Allen & Unwin', 'slug' => 'allen-unwin', 'country' => 'Великобритания', 'since' => '1914-08-04'],
            ['title' => 'Bloomsbury Publishing', 'slug' => 'bloomsbury', 'country' => 'Великобритания', 'since' => '1986-09-26'],
            ['title' => 'Chilton Books', 'slug' => 'chilton-books', 'country' => 'США', 'since' => '1955-01-01'],
            ['title' => 'CD Projekt Red', 'slug' => 'cd-projekt-red', 'country' => 'Польша', 'since' => '2002-02-01'],
        ];

        foreach ($companiesData as $cmp) {
            Article::updateOrCreate(
                ['slug' => $cmp['slug']],
                [
                    'title'       => $cmp['title'],
                    'template_id' => $tplCompany,
                    'status'      => 'published',
                    'data'        => [
                        'name_ru'   => $cmp['title'],
                        'full_name' => $cmp['title'] . ' Ltd.',
                        'country'   => $cmp['country'],
                        'birthday'  => $cmp['since'],
                    ],
                    'content'     => "Издательская / медийная компания {$cmp['title']}.",
                ]
            );
        }

        // 7. Жанры (4)
        $genresData = [
            ['title' => 'Эпическое фэнтези', 'slug' => 'epic-fantasy', 'desc' => 'Поджанр фантастики, определяемый масштабностью событий, мифологическим размахом и борьбой со сверхъестественным злом.'],
            ['title' => 'Научная фантастика', 'slug' => 'science-fiction', 'desc' => 'Жанр в литературе и кино, где события строятся вокруг научных открытий, космических путешествий и будущего.'],
            ['title' => 'Киберпанк', 'slug' => 'genre-cyberpunk', 'desc' => 'High tech, low life: мир высоких технологий на фоне социального упадка и власти мегакорпораций.'],
            ['title' => 'Космическая опера', 'slug' => 'space-opera', 'desc' => 'Приключенческий жанр фантастики с акцентом на межзвёздные сражения, интриги империй и космические путешествия.'],
        ];

        foreach ($genresData as $g) {
            Article::updateOrCreate(
                ['slug' => $g['slug']],
                [
                    'title'       => $g['title'],
                    'template_id' => $tplGenre,
                    'status'      => 'published',
                    'data'        => [],
                    'content'     => $g['desc'],
                ]
            );
        }

        // 8. Новости (3) и Серия новостей (1)
        $newsSeries = Article::updateOrCreate(
            ['slug' => 'news-series-main'],
            [
                'title'       => 'Главные новости фантастики',
                'template_id' => $tplNewsSeries,
                'status'      => 'published',
                'data'        => [],
                'content'     => 'Лента главных событий, релизов и анонсов фантастических произведений.',
            ]
        );

        $newsData = [
            ['title' => 'Анонс коллекционного переиздания «Властелина Колец»', 'slug' => 'news-lotr-announcement', 'teaser' => true],
            ['title' => 'Премьера новой главы саги по вселенной Дюны', 'slug' => 'news-dune-premiere', 'teaser' => false],
            ['title' => 'Масштабный фестиваль по миру Найт-Сити', 'slug' => 'news-cyberpunk-festival', 'teaser' => false],
        ];

        foreach ($newsData as $n) {
            Article::updateOrCreate(
                ['slug' => $n['slug']],
                [
                    'title'       => $n['title'],
                    'template_id' => $tplNews,
                    'parent_id'   => $newsSeries->id,
                    'status'      => 'published',
                    'data'        => ['is_teaser' => $n['teaser']],
                    'content'     => "Свежие новости мира фантастики: {$n['title']}.",
                ]
            );
        }

        // Построение и пересчет fullslug для всех статей от корня
        foreach (Article::cursor() as $art) {
            $art->fullslug = $art->calculateFullSlug();
            $art->saveQuietly(['force' => true]);
        }

        // Перестройка иерархических индексов дерева NestedTree
        (new Article)->resetTreeNesting();
    }
}

