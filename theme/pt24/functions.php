<?php
/**
 * PT24.PRO Theme Functions
 *
 * @package PT24
 * @version 1.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}

define('PT24_VERSION', '1.0.3');
define('PT24_DIR', get_template_directory());
define('PT24_URI', get_template_directory_uri());

/**
 * Theme setup
 */
function pt24_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', [
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ]);
    add_theme_support('custom-logo', [
        'height'      => 40,
        'width'       => 40,
        'flex-height' => true,
        'flex-width'  => true,
    ]);

    register_nav_menus([
        'primary'   => __('Menu główne', 'pt24'),
        'footer'    => __('Menu stopki', 'pt24'),
    ]);
}
add_action('after_setup_theme', 'pt24_setup');

/**
 * Enqueue scripts and styles
 */
function pt24_scripts() {
    $pt24_theme_css_path = PT24_DIR . '/assets/css/pt24-theme.css';
    $pt24_theme_js_path = PT24_DIR . '/assets/js/pt24-theme.js';
    $pt24_theme_css_ver = file_exists($pt24_theme_css_path) ? (string) filemtime($pt24_theme_css_path) : PT24_VERSION;
    $pt24_theme_js_ver = file_exists($pt24_theme_js_path) ? (string) filemtime($pt24_theme_js_path) : PT24_VERSION;

    // Google Fonts
    wp_enqueue_style(
        'pt24-google-fonts',
        'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@700;800&display=swap',
        [],
        null
    );

    // Tailwind CDN (production should use compiled CSS)
    wp_enqueue_script(
        'pt24-tailwind',
        'https://cdn.tailwindcss.com',
        [],
        null,
        false
    );

    // Tailwind config — must appear right after the CDN script
    wp_add_inline_script('pt24-tailwind', pt24_tailwind_config());

    // Theme CSS
    wp_enqueue_style(
        'pt24-theme',
        PT24_URI . '/assets/css/pt24-theme.css',
        [],
        $pt24_theme_css_ver
    );

    // Theme JS
    wp_enqueue_script(
        'pt24-theme',
        PT24_URI . '/assets/js/pt24-theme.js',
        [],
        $pt24_theme_js_ver,
        true
    );
}
add_action('wp_enqueue_scripts', 'pt24_scripts');

/**
 * Public PT24 base URL used in frontend SEO/link output.
 */
function pt24_public_base_url() {
    return 'https://pt24.pro';
}

/**
 * Whether URL rewriting should run for the current request.
 *
 * Keep wp-admin/AJAX/REST internals untouched.
 */
function pt24_should_rewrite_public_urls() {
    if (is_admin() || wp_doing_ajax()) {
        return false;
    }

    // wp-login.php is outside wp-admin, but must keep core URL/cookie behavior intact.
    $pagenow = isset($GLOBALS['pagenow']) ? (string) $GLOBALS['pagenow'] : '';
    if ($pagenow === 'wp-login.php' || $pagenow === 'wp-register.php') {
        return false;
    }

    if (defined('REST_REQUEST') && REST_REQUEST) {
        return false;
    }

    if (defined('WP_CLI') && WP_CLI) {
        return false;
    }

    $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
    if ($request_uri !== '') {
        $request_path = (string) wp_parse_url($request_uri, PHP_URL_PATH);
        $core_prefixes = [
            '/wp-admin',
            '/wp-login.php',
            '/wp-register.php',
            '/wp-json',
            '/xmlrpc.php',
        ];

        foreach ($core_prefixes as $prefix) {
            if (strpos($request_path, $prefix) === 0) {
                return false;
            }
        }
    }

    return true;
}

/**
 * Rewrite origin host URLs to the public PT24 domain.
 *
 * @param string $url URL to normalize.
 * @return string
 */
function pt24_rewrite_to_public_url($url) {
    if (! is_string($url) || $url === '') {
        return $url;
    }

    return str_replace(
        [
            'https://wordpress2614653.home.pl/pt24',
            'http://wordpress2614653.home.pl/pt24',
            '//wordpress2614653.home.pl/pt24',
            'https:\\/\\/wordpress2614653.home.pl\\/pt24',
            'http:\\/\\/wordpress2614653.home.pl\\/pt24',
            'wordpress2614653.home.pl%2Fpt24',
            'wordpress2614653.home.pl%2fpt24',
            'wordpress2614653.home.pl',
            'https://pt24.pro/pt24',
            'http://pt24.pro/pt24',
            '//pt24.pro/pt24',
            'https:\\/\\/pt24.pro\\/pt24',
            'http:\\/\\/pt24.pro\\/pt24',
        ],
        [
            pt24_public_base_url(),
            pt24_public_base_url(),
            str_replace('https:', '', pt24_public_base_url()),
            'https:\\/\\/pt24.pro',
            'https:\\/\\/pt24.pro',
            'pt24.pro',
            'pt24.pro',
            'pt24.pro',
            pt24_public_base_url(),
            pt24_public_base_url(),
            str_replace('https:', '', pt24_public_base_url()),
            'https:\\/\\/pt24.pro',
            'https:\\/\\/pt24.pro',
        ],
        $url
    );
}

/**
 * Frontend-only URL normalization for core-generated links.
 */
function pt24_filter_public_frontend_url($url) {
    if (! pt24_should_rewrite_public_urls()) {
        return $url;
    }

    return pt24_rewrite_to_public_url($url);
}
add_filter('home_url', 'pt24_filter_public_frontend_url', 20);
add_filter('site_url', 'pt24_filter_public_frontend_url', 20);
add_filter('rest_url', 'pt24_filter_public_frontend_url', 20);
add_filter('get_shortlink', 'pt24_filter_public_frontend_url', 20);

/**
 * Keep Yoast canonical and OG URL on the public PT24 domain.
 */
function pt24_filter_wpseo_public_url($url) {
    if (! pt24_should_rewrite_public_urls()) {
        return $url;
    }

    $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
    $request_path = (string) wp_parse_url($request_uri, PHP_URL_PATH);
    $segments = array_values(array_filter(explode('/', trim($request_path, '/'))));

    if (!empty($segments) && strtolower((string) $segments[0]) === 'pt24') {
        array_shift($segments);
    }

    if (!empty($segments) && strtolower((string) $segments[0]) === 'uslugi') {
        if (isset($segments[1]) && $segments[1] !== '') {
            return pt24_public_base_url() . '/uslugi/' . sanitize_title((string) $segments[1]) . '/';
        }
        return pt24_public_base_url() . '/uslugi/';
    }

    if (!empty($segments) && strtolower((string) $segments[0]) === 'miasta') {
        return pt24_public_base_url() . '/miasta/';
    }

    if (!empty($segments) && in_array(strtolower((string) $segments[0]), ['panel', 'panel-firmy', 'admin'], true)) {
        return pt24_public_base_url() . '/' . sanitize_title((string) $segments[0]) . '/';
    }

    if (! empty($segments) && is_array(pt24_get_frontend_page_by_slug((string) $segments[0]))) {
        return pt24_public_base_url() . '/' . sanitize_title((string) $segments[0]) . '/';
    }

    return pt24_filter_public_frontend_url($url);
}
add_filter('wpseo_canonical', 'pt24_filter_wpseo_public_url', 20);
add_filter('wpseo_opengraph_url', 'pt24_filter_wpseo_public_url', 20);

/**
 * Route-aware title correction for PT24 custom paths.
 */
function pt24_pre_get_document_title($title) {
    if (! pt24_should_rewrite_public_urls()) {
        return $title;
    }

    $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
    $request_path = (string) wp_parse_url($request_uri, PHP_URL_PATH);
    $segments = array_values(array_filter(explode('/', trim($request_path, '/'))));

    if (!empty($segments) && strtolower((string) $segments[0]) === 'pt24') {
        array_shift($segments);
    }

    if (!empty($segments) && strtolower((string) $segments[0]) === 'uslugi') {
        if (isset($segments[1]) && $segments[1] !== '') {
            return ucfirst(str_replace('-', ' ', sanitize_title((string) $segments[1]))) . ' - PT24.PRO';
        }
        return 'Usługi - PT24.PRO';
    }

    if (!empty($segments) && strtolower((string) $segments[0]) === 'miasta') {
        return 'Miasta - PT24.PRO';
    }

    if (!empty($segments) && strtolower((string) $segments[0]) === 'panel') {
        return 'Panel użytkownika - PT24.PRO';
    }

    if (!empty($segments) && strtolower((string) $segments[0]) === 'panel-firmy') {
        return 'Panel firmy - PT24.PRO';
    }

    if (!empty($segments) && strtolower((string) $segments[0]) === 'admin') {
        return 'Panel administratora - PT24.PRO';
    }

    if (! empty($segments)) {
        $page = pt24_get_frontend_page_by_slug((string) $segments[0]);
        if (isset($page['title']) && is_string($page['title']) && $page['title'] !== '') {
            return $page['title'] . ' - PT24.PRO';
        }
    }

    return $title;
}
add_filter('pre_get_document_title', 'pt24_pre_get_document_title', 20);

/**
 * PT24 static marketing pages config.
 *
 * @return array<string, array<string, mixed>>
 */
function pt24_get_static_frontend_pages() {
    return [
        'jak-to-dziala' => [
            'title' => 'Jak Dziala PT24',
            'eyebrow' => 'Proces',
            'lead' => 'PT24 skraca droge od problemu do realizacji. W 60 sekund wysylasz jedno zapytanie, a nasz silnik dopasowuje lokalnych specjalistow gotowych do szybkiej odpowiedzi.',
            'highlights' => ['Formularz 60 sekund', 'Dopasowanie po miescie i usludze', 'Pierwsze oferty nawet w 15 min'],
            'sections' => [
                ['title' => 'Krok 1: Brief zlecenia', 'text' => 'Wpisujesz czego potrzebujesz, gdzie i na kiedy. Formularz prowadzi Cie przez najwazniejsze dane, ktore przyspieszaja wycene.'],
                ['title' => 'Krok 2: Matchmaking AI', 'text' => 'Algorytm wybiera wykonawcow po specjalizacji, lokalizacji i aktywnosci. Zapytanie trafia tylko do firm, ktore realnie moga je obsluzyc.'],
                ['title' => 'Krok 3: Porownanie ofert', 'text' => 'Otrzymujesz odpowiedzi z terminem i orientacyjna cena. Wybierasz najkorzystniejsza opcje i kontaktujesz sie bezposrednio z firma.'],
            ],
        ],
        'rankingi' => [
            'title' => 'Rankingi Fachowcow',
            'eyebrow' => 'Rankingi',
            'lead' => 'Przegladaj rankingi wykonawcow wedlug specjalizacji i miasta. Zobacz kto odpowiada najszybciej i ma najlepsze oceny od klientow.',
            'highlights' => ['Rankingi wg uslug', 'Porownanie miast', 'Oceny i czas odpowiedzi'],
            'sections' => [
                ['title' => 'Jak czytac rankingi', 'text' => 'Pozycja firmy zalezy od ocen, aktywnosci oraz szybkosci odpowiedzi na zapytania klientow.'],
                ['title' => 'Najpopularniejsze kategorie', 'text' => 'Hydraulik, elektryk, mechanik, dekarz i klimatyzacja to kategorie z najwieksza liczba aktywnych wykonawcow.'],
                ['title' => 'Ranking lokalny', 'text' => 'Wybierz miasto i kategorie uslugi, aby zobaczyc firmy, ktore realnie dzialaja w Twojej okolicy.'],
            ],
        ],
        'dla-firm' => [
            'title' => 'Dla Firm',
            'eyebrow' => 'B2B',
            'lead' => 'Pozyskuj klientow bez przepalania budzetu reklamowego. PT24 dostarcza intencyjne leady lokalne i daje pełna kontrole kosztu pozyskania przez model kredytowy.',
            'highlights' => ['Leady intencyjne', 'Panel firmy i CRM', 'Rozliczenie per przypisanie'],
            'sections' => [
                ['title' => 'Leady gotowe do obslugi', 'text' => 'Nowe zapytania wpadaja do panelu firmy od klientow, ktorzy szukaja wykonawcy teraz, a nie za kilka miesiecy.'],
                ['title' => 'Widocznosc skutecznosci', 'text' => 'Widzisz konwersje, liczbe przypisanych leadow, saldo kredytow i historie transakcji w jednym miejscu.'],
                ['title' => 'Skalowanie bez chaosu', 'text' => 'Zmiana planu i dokupienie pakietu trwa chwile, dzieki czemu mozesz zwiekszac wolumen wtedy, gdy rosnie popyt.'],
            ],
        ],
        'hydraulik-dla-firm' => [
            'title' => 'Hydraulik Dla Firm',
            'eyebrow' => 'SEO Lead Page',
            'lead' => 'Leady dla hydraulikow z Twojego miasta: awarie, przecieki, montaze i serwis. Otrzymuj zgloszenia od klientow, ktorzy szukaja pomocy teraz.',
            'highlights' => ['Awarie 24/7', 'Zapytania lokalne', 'Mniej przypadkowych kontaktow'],
            'sections' => [
                ['title' => 'Jakie zapytania trafiaja do hydraulika', 'text' => 'Najczesciej sa to pilne awarie, udraznianie, montaz armatury i modernizacje instalacji w mieszkaniach oraz domach.'],
                ['title' => 'Dlaczego konwersja jest wyzsza', 'text' => 'Klient zostawia konkretne dane i opis usterki, dzieki czemu szybciej przygotujesz wycene i termin realizacji.'],
                ['title' => 'Model rozliczen dla hydraulika', 'text' => 'Placisz za przypisane leady. W panelu widzisz saldo, historie rozliczen i skutecznosc odpowiedzi.'],
            ],
        ],
        'elektryk-dla-firm' => [
            'title' => 'Elektryk Dla Firm',
            'eyebrow' => 'SEO Lead Page',
            'lead' => 'Pozyskuj leady dla elektryka bez czekania na polecenia. PT24 kieruje do Ciebie zapytania z montazu, napraw i modernizacji instalacji.',
            'highlights' => ['Zlecenia instalacyjne', 'Modernizacje i awarie', 'Kontrola kosztu pozyskania'],
            'sections' => [
                ['title' => 'Typowe zlecenia dla elektryka', 'text' => 'Awarie pradu, wymiana rozdzielni, podlaczenia sprzetu i instalacje w nowych lokalach.'],
                ['title' => 'Priorytet szybkiej odpowiedzi', 'text' => 'W branzy elektrycznej szybki kontakt daje przewage. PT24 skraca czas od zapytania do pierwszej rozmowy.'],
                ['title' => 'Skalowanie kampanii leadowej', 'text' => 'W okresach wiekszego popytu dokupujesz kredyty i zwiekszasz wolumen bez przebudowy procesu sprzedazy.'],
            ],
        ],
        'mechanik-dla-firm' => [
            'title' => 'Mechanik Dla Firm',
            'eyebrow' => 'SEO Lead Page',
            'lead' => 'Leady dla warsztatow i mechanikow z Twojego regionu. Otrzymuj zapytania o diagnostyke, naprawy i szybkie interwencje.',
            'highlights' => ['Zapytania motoryzacyjne', 'Lokalny zasieg', 'Wiecej terminow w grafiku'],
            'sections' => [
                ['title' => 'Jakie leady dostaje mechanik', 'text' => 'Klienci pytaja o wyceny napraw, wymiany eksploatacyjne, diagnostyke i pomoc przy naglych awariach.'],
                ['title' => 'Lepsza organizacja warsztatu', 'text' => 'Dzieki czytelnym danym klienta i opisu problemu szybciej kwalifikujesz przypadek i planujesz termin.'],
                ['title' => 'Przewidywalnosc kosztow', 'text' => 'Model kredytowy pozwala oszacowac koszt pozyskania klienta i porownac go z wartoscia zlecenia.'],
            ],
        ],
        'remonty-dla-firm' => [
            'title' => 'Remonty Dla Firm',
            'eyebrow' => 'SEO Lead Page',
            'lead' => 'Zbieraj leady remontowe o wyzszym budzecie: lazienki, kuchnie, wykonczenia i kompleksowe prace modernizacyjne.',
            'highlights' => ['Wyzsze koszyki zlecen', 'Zapytania z zakresem prac', 'Lepsze planowanie ekip'],
            'sections' => [
                ['title' => 'Jakie projekty remontowe trafiaja do PT24', 'text' => 'Od drobnych poprawek po kompleksowe remonty z harmonogramem i orientacyjnym zakresem budzetowym.'],
                ['title' => 'Dlaczego to dobre zrodlo leadow', 'text' => 'Klient porownuje oferty i jest gotowy do decyzji, a Ty dostajesz konkretne zapytanie zamiast ogolnej prosby.'],
                ['title' => 'Jak rosnac na leadach remontowych', 'text' => 'Budujesz pipeline zlecen, analizujesz skutecznosc i wzmacniasz widocznosc firmy w najbardziej dochodowych segmentach.'],
            ],
        ],
        'dla-fachowcow' => [
            'title' => 'Dla Fachowcow',
            'eyebrow' => 'Wykonawcy',
            'lead' => 'Jesli jestes hydraulikiem, elektrykiem, mechanikiem lub wykonawca remontowym, PT24 pomaga zamienic puste okna w kalendarzu na realne zlecenia.',
            'highlights' => ['Lokalne zlecenia', 'Szybkie uruchomienie profilu', 'Przewidywalny koszt leada'],
            'sections' => [
                ['title' => 'Profil ekspercki', 'text' => 'Dodajesz zakres uslug, miasta dzialania i dane kontaktowe. Klient od razu wie, w czym sie specjalizujesz.'],
                ['title' => 'Leady zgodne z profilem', 'text' => 'Dostajesz tylko zapytania z pasujacej kategorii i lokalizacji, co poprawia skutecznosc odpowiedzi.'],
                ['title' => 'Wzrost przychodu', 'text' => 'Szybkie odpowiedzi i profesjonalna obsluga leada przekladaja sie na wyzsza konwersje i wiecej realizacji.'],
            ],
        ],
        'o-nas' => [
            'title' => 'O Nas',
            'eyebrow' => 'Zespol',
            'lead' => 'PT24 to polski marketplace uslug lokalnych, ktory laczy klienta z wykonawca szybciej niz klasyczne katalogi i ogloszenia.',
            'highlights' => ['Marketplace lokalny', 'Silnik dopasowania', 'Podejscie data-driven'],
            'sections' => [
                ['title' => 'Dlaczego powstal PT24', 'text' => 'Wielu klientow traci czas na dziesiatki telefonow. U nas jedno zapytanie otwiera dostep do wielu ofert.'],
                ['title' => 'Jakosc i zaufanie', 'text' => 'Wspieramy proces od pierwszego kontaktu po wybor wykonawcy, stawiajac na transparentnosc i konkret.'],
                ['title' => 'Roadmapa', 'text' => 'Rozwijamy kolejne miasta, nowe kategorie oraz automatyzacje dla firm, aby podnosic jakosc leadow.'],
            ],
        ],
        'kontakt' => [
            'title' => 'Kontakt',
            'eyebrow' => 'Pomoc',
            'lead' => 'Skontaktuj sie z nami w sprawie obslugi zlecen, partnerstw, wdrozen B2B i wsparcia technicznego dla kont firmowych.',
            'highlights' => ['Wsparcie platformy', 'Partnerstwa B2B', 'Onboarding firm'],
            'sections' => [
                ['title' => 'Kontakt ogolny', 'text' => 'kontakt@pt24.pro'],
                ['title' => 'Dzial firmy i partnerstwa', 'text' => 'business@pt24.pro'],
                ['title' => 'Godziny wsparcia', 'text' => 'Poniedzialek-Piatek, 8:00-17:00, odpowiedz zwykle tego samego dnia'],
            ],
        ],
        'dodaj-zlecenie' => [
            'title' => 'Dodaj Zlecenie',
            'eyebrow' => 'Konwersja',
            'lead' => 'Wypelnij krotki formularz i otrzymaj oferty od lokalnych wykonawcow dopasowanych do zakresu prac i miasta.',
            'highlights' => ['Formularz online', 'Dopasowanie lokalne', 'Szybka odpowiedz firm'],
            'sections' => [
                ['title' => 'Jak dodac zlecenie', 'text' => 'Podaj miasto, zakres prac i preferowany termin realizacji. Im bardziej konkretny opis, tym trafniejsze odpowiedzi.'],
                ['title' => 'Co dzieje sie po wysylce', 'text' => 'Zapytanie trafia do panelu firm z odpowiedniej kategorii i lokalizacji, a wykonawcy moga szybko odeslac oferty.'],
                ['title' => 'Jak porownac oferty', 'text' => 'Zwroc uwage na termin, zakres i koszt realizacji. Wybierz wykonawce, ktory najlepiej odpowiada Twoim potrzebom.'],
            ],
        ],
        'cennik' => [
            'title' => 'Cennik',
            'eyebrow' => 'Monetyzacja',
            'lead' => 'Wybierz model wzrostu dopasowany do skali firmy. Startujesz od planu bazowego i dokupujesz kredyty wtedy, gdy potrzebujesz wiekszego wolumenu leadow.',
            'highlights' => ['Free, Starter, Pro, Enterprise', 'Pakiety kredytowe 25/60/150', 'Pelen wglad w historie rozliczen'],
            'sections' => [
                ['title' => 'Plany abonamentowe', 'text' => 'Plan okresla podstawowe limity i dostep do funkcji panelu. Upgrade i downgrade robisz samodzielnie.'],
                ['title' => 'Kredyty leadowe', 'text' => 'Przypisanie leada konsumuje kredyt. System zapisuje transakcje i pilnuje, aby saldo nie schodzilo ponizej zera.'],
                ['title' => 'ROI i kontrola kosztow', 'text' => 'Masz pelny podglad kosztu pozyskania klienta i mozesz skalowac budzet tylko tam, gdzie widzisz zwrot.'],
            ],
        ],
        'faq' => [
            'title' => 'FAQ',
            'eyebrow' => 'Pytania',
            'lead' => 'Zebrane odpowiedzi na pytania klientow i firm o dzialanie leadow, szybkosci odpowiedzi, rozliczenia i jakosc zapytan.',
            'highlights' => ['Pytania klientow', 'Pytania firm', 'Operacje i rozliczenia'],
            'sections' => [
                ['title' => 'Czy PT24 jest darmowe dla klienta?', 'text' => 'Tak, zglaszanie zapytan i porownywanie ofert jest bezplatne dla klientow.'],
                ['title' => 'Jak szybko firma dostaje lead?', 'text' => 'Lead trafia do panelu po dopasowaniu, zazwyczaj natychmiast. Najlepsze firmy odpowiadaja nawet w kilkanascie minut.'],
                ['title' => 'Czy moge zmienic plan firmy?', 'text' => 'Tak, plan i pakiety kredytow zmieniasz bezposrednio w panelu firmy, bez kontaktu z supportem.'],
            ],
        ],
        'regulamin' => [
            'title' => 'Regulamin',
            'eyebrow' => 'Prawo',
            'lead' => 'Dokument okresla zasady korzystania z platformy PT24 przez klientow i firmy oraz warunki swiadczenia uslug online.',
            'highlights' => ['Warunki platformy', 'Odpowiedzialnosc stron', 'Konta i platnosci'],
            'sections' => [
                ['title' => 'Zakres uslugi', 'text' => 'PT24 jest platforma posredniczaca i nie jest strona umowy o wykonanie uslugi.'],
                ['title' => 'Konta i profile', 'text' => 'Uzytkownik odpowiada za prawdziwosc danych, legalnosc publikowanych informacji i bezpieczenstwo konta.'],
                ['title' => 'Postanowienia koncowe', 'text' => 'Aktualna wersja regulaminu obowiazuje od dnia publikacji i moze byc aktualizowana wraz z rozwojem uslugi.'],
            ],
        ],
        'polityka-prywatnosci' => [
            'title' => 'Polityka Prywatnosci',
            'eyebrow' => 'RODO',
            'lead' => 'Wyjasniamy jakie dane zbieramy, jak je chronimy i jakie prawa przysluguja Ci jako uzytkownikowi zgodnie z RODO.',
            'highlights' => ['Zakres danych', 'Podstawa prawna', 'Prawa uzytkownika'],
            'sections' => [
                ['title' => 'Zakres danych', 'text' => 'Przetwarzamy dane kontaktowe i operacyjne niezbedne do obslugi zapytan i przekazania ich do wykonawcow.'],
                ['title' => 'Podstawa prawna', 'text' => 'Podstawa przetwarzania to zgoda, realizacja umowy oraz uzasadniony interes administratora.'],
                ['title' => 'Twoje prawa', 'text' => 'Masz prawo dostepu do danych, ich poprawienia, usuniecia, ograniczenia przetwarzania i wniesienia sprzeciwu.'],
            ],
        ],
    ];
}

/**
 * Supported v5 service segments for B2B pages.
 *
 * @return array<string, string>
 */
function pt24_get_segment_service_titles() {
    return [
        'hydraulik' => 'Hydraulik',
        'elektryk' => 'Elektryk',
        'mechanik' => 'Mechanik',
        'remonty' => 'Remonty',
        'dekarz' => 'Dekarz',
        'klimatyzacja' => 'Klimatyzacja',
        'brukarz' => 'Brukarz',
        'instalacje' => 'Instalacje',
        'pompy-ciepla' => 'Pompy Ciepla',
        'fotowoltaika' => 'Fotowoltaika',
        'prawo' => 'Prawo',
    ];
}

/**
 * Supported v5 city segments for B2B pages.
 *
 * @return array<string, string>
 */
function pt24_get_segment_city_titles() {
    return [
        'warszawa' => 'Warszawa',
        'krakow' => 'Krakow',
        'wroclaw' => 'Wroclaw',
        'poznan' => 'Poznan',
        'gdansk' => 'Gdansk',
        'katowice' => 'Katowice',
    ];
}

/**
 * Parse dynamic segment page slug into structured data.
 *
 * @param string $slug Slug candidate.
 * @return array<string, string>|null
 */
function pt24_parse_segment_page_slug($slug) {
    $slug = sanitize_title((string) $slug);
    if ($slug === '') {
        return null;
    }

    $services = pt24_get_segment_service_titles();
    $cities = pt24_get_segment_city_titles();
    $service_pattern = implode('|', array_map('preg_quote', array_keys($services)));
    $city_pattern = implode('|', array_map('preg_quote', array_keys($cities)));

    $regex = '/^(' . $service_pattern . ')-dla-firm-(' . $city_pattern . ')$/';
    if (preg_match($regex, $slug, $m) !== 1) {
        return null;
    }

    $service_slug = sanitize_key((string) $m[1]);
    $city_slug = sanitize_key((string) $m[2]);

    return [
        'service_slug' => $service_slug,
        'service_title' => isset($services[$service_slug]) ? $services[$service_slug] : ucfirst($service_slug),
        'city_slug' => $city_slug,
        'city_title' => isset($cities[$city_slug]) ? $cities[$city_slug] : ucfirst($city_slug),
    ];
}

/**
 * Resolve a frontend marketing page by slug.
 *
 * Supports static pages and dynamic city variants, e.g.:
 * - hydraulik-dla-firm-warszawa
 * - elektryk-dla-firm-krakow
 *
 * @param string $slug Page slug.
 * @return array<string, mixed>|null
 */
function pt24_get_frontend_page_by_slug($slug) {
    $slug = sanitize_title((string) $slug);
    if ($slug === '') {
        return null;
    }

    $pages = pt24_get_static_frontend_pages();
    if (isset($pages[$slug]) && is_array($pages[$slug])) {
        return $pages[$slug];
    }

    $parsed_segment = pt24_parse_segment_page_slug($slug);
    if (is_array($parsed_segment)) {
        $service = (string) $parsed_segment['service_title'];
        $city = (string) $parsed_segment['city_title'];

        return [
            'title' => $service . ' Dla Firm ' . $city,
            'eyebrow' => 'SEO Local',
            'lead' => 'Lokalne leady dla branzy ' . strtolower($service) . ' w miescie ' . $city . '. Docieraj do klientow, ktorzy chca zamowic usluge teraz.',
            'highlights' => ['Leady ' . $city, 'Szybkie zapytania lokalne', 'Kontrola kosztu pozyskania'],
            'sections' => [
                ['title' => 'Popyt lokalny: ' . $city, 'text' => 'Klienci z miasta ' . $city . ' szukaja sprawdzonych wykonawcow i porownuja oferty online.'],
                ['title' => 'Leady dla ' . strtolower($service), 'text' => 'Zapytania zawieraja kluczowe dane: lokalizacje, zakres prac i pilnosc, co przyspiesza kontakt handlowy.'],
                ['title' => 'Wzrost firmy w ' . $city, 'text' => 'Staly doplyw zapytan pomaga wypelnic grafik, poprawic konwersje i stabilizowac przychody w danym miescie.'],
            ],
        ];
    }

    return null;
}

/**
 * Rewrite any remaining origin-host URLs in final frontend HTML.
 *
 * This catches Yoast JSON-LD fields that may bypass URL filters.
 */
function pt24_buffer_public_host() {
    if (! pt24_should_rewrite_public_urls()) {
        return;
    }

    ob_start('pt24_rewrite_public_host_html');
}

/**
 * Output-buffer callback for host normalization.
 *
 * @param string $html Rendered HTML.
 * @return string
 */
function pt24_rewrite_public_host_html($html) {
    if (! is_string($html) || $html === '') {
        return $html;
    }

    return pt24_rewrite_to_public_url($html);
}
add_action('template_redirect', 'pt24_buffer_public_host', 1);

/**
 * Prevent WordPress from deferring the Tailwind CDN script.
 * Tailwind CDN must execute synchronously before the page renders.
 */
function pt24_prevent_tailwind_defer($tag, $handle) {
    if ($handle === 'pt24-tailwind') {
        // Remove any defer/async attributes WP may add
        $tag = str_replace(' defer', '', $tag);
        $tag = str_replace(' async', '', $tag);
        // Ensure blocking render
        $tag = str_replace(' type="text/javascript"', '', $tag);
    }
    return $tag;
}
add_filter('script_loader_tag', 'pt24_prevent_tailwind_defer', 10, 2);

/**
 * Tailwind configuration inline script
 */
function pt24_tailwind_config() {
    return "tailwind.config = {
        theme: {
            extend: {
                colors: {
                    brand: { start: '#1464F4', end: '#7A4FD3', mid: '#4A5FE3' },
                    pear: { green: '#4ADE80', blue: '#60A5FA' }
                },
                fontFamily: {
                    display: ['Poppins', 'system-ui', 'sans-serif'],
                    body: ['Inter', 'system-ui', 'sans-serif']
                },
                boxShadow: {
                    soft: '0 20px 60px -28px rgba(15,23,42,0.35)',
                    card: '0 4px 24px -4px rgba(15,23,42,0.08)',
                    glow: '0 0 40px -8px rgba(20,100,244,0.3)'
                }
            }
        }
    };";
}

/**
 * Get PT24 brand colors
 */
function pt24_get_colors() {
    return [
        'brand_start' => '#1464F4',
        'brand_mid'   => '#4A5FE3',
        'brand_end'   => '#7A4FD3',
        'pear_green'  => '#4ADE80',
        'pear_blue'   => '#60A5FA',
    ];
}

/**
 * Service categories data
 */
function pt24_get_categories() {
    return [
        ['name' => 'Hydraulik',                'slug' => '/hydraulik/',      'icon' => '💧'],
        ['name' => 'Elektryk',                 'slug' => '/elektryk/',       'icon' => '⚡'],
        ['name' => 'Mechanik samochodowy',     'slug' => '/mechanik/',       'icon' => '🔧'],
        ['name' => 'Klimatyzacja i wentylacja','slug' => '/klimatyzacja/',   'icon' => '❄️'],
        ['name' => 'Informatyk / IT',          'slug' => '/informatyk/',     'icon' => '💻'],
        ['name' => 'Złota rączka',             'slug' => '/zlota-raczka/',   'icon' => '🛠️'],
        ['name' => 'Malarz / Wykończenia',     'slug' => '/malarz/',         'icon' => '🎨'],
        ['name' => 'Przeprowadzki',            'slug' => '/przeprowadzki/',  'icon' => '📦'],
        ['name' => 'Ogrodnik',                 'slug' => '/ogrodnik/',       'icon' => '🌱'],
    ];
}

/**
 * Popular searches data
 */
function pt24_get_popular_searches() {
    return [
        'Hydraulik Warszawa',
        'Hydraulik Kraków',
        'Elektryk Warszawa',
        'Elektryk Kraków',
        'Mechanik Katowice',
        'Informatyk Wrocław',
        'Klimatyzacja Poznań',
        'Złota rączka Gdańsk',
        'Malarz Łódź',
    ];
}

/**
 * Testimonials data
 */
function pt24_get_testimonials() {
    return [
        [
            'text'     => '„Pękła rura w kuchni o 21:30. Przez PT24 dostałam kontakt do hydraulika z mojej dzielnicy i następnego dnia rano problem był rozwiązany.”',
            'author'   => 'Anna Kaczmarek',
            'location' => 'Warszawa',
        ],
        [
            'text'     => '„Szukaliśmy elektryka do modernizacji instalacji w mieszkaniu. Porównaliśmy 3 oferty, wybraliśmy najlepszą i wszystko było zrobione terminowo.”',
            'author'   => 'Michał Wójcik',
            'location' => 'Kraków',
        ],
        [
            'text'     => '„Auto nie odpalało po weekendzie. Zgłoszenie dodałem rano, a po godzinie miałem umówioną diagnostykę u mechanika 10 minut od domu.”',
            'author'   => 'Karolina Maj',
            'location' => 'Katowice',
        ],
        [
            'text'     => '„Montaż klimatyzacji przebiegł sprawnie, a wykonawca od razu wyjaśnił jak serwisować urządzenie. Ceny i terminy były jasne od początku.”',
            'author'   => 'Paweł Duda',
            'location' => 'Poznań',
        ],
        [
            'text'     => '„Potrzebowałam pilnie malowania mieszkania przed wynajmem. Dzięki PT24 szybko znalazłam ekipę, która weszła w dogodnym terminie.”',
            'author'   => 'Ewa Grabowska',
            'location' => 'Łódź',
        ],
        [
            'text'     => '„Przy remoncie łazienki najważniejsza była dla mnie weryfikacja opinii. Wybrany fachowiec dotrzymał wyceny i zakończył pracę zgodnie z planem.”',
            'author'   => 'Tomasz Lewandowski',
            'location' => 'Gdańsk',
        ],
    ];
}

/**
 * Yandex Metrica tracking code.
 * Set counter ID via: wp option update pt24_yandex_metrica_id YOUR_ID
 */
function pt24_yandex_metrica() {
    $counter_id = get_option('pt24_yandex_metrica_id', '');
    if (empty($counter_id) || is_admin()) {
        return;
    }
    ?>
    <!-- Yandex.Metrika counter -->
    <script type="text/javascript">
        (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
        m[i].l=1*new Date();
        for(var j=0;j<document.scripts.length;j++){if(document.scripts[j].src===r)return;}
        k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
        (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");

        ym(<?php echo esc_js($counter_id); ?>, "init", {
            clickmap:true,
            trackLinks:true,
            accurateTrackBounce:true,
            webvisor:true
        });
    </script>
    <noscript><div><img src="https://mc.yandex.ru/watch/<?php echo esc_attr($counter_id); ?>" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
    <!-- /Yandex.Metrika counter -->
    <?php
}
add_action('wp_head', 'pt24_yandex_metrica', 99);

/**
 * Register custom panel routes.
 */
function pt24_register_panel_routes() {
    add_rewrite_rule('^panel/?$', 'index.php?pt24_panel=user', 'top');
    add_rewrite_rule('^panel-firmy/?$', 'index.php?pt24_panel=company', 'top');
    add_rewrite_rule('^admin/?$', 'index.php?pt24_panel=admin', 'top');
    add_rewrite_rule('^uslugi/?$', 'index.php?pt24_service_hub=index', 'top');
    add_rewrite_rule('^uslugi/([^/]+)/?$', 'index.php?pt24_service_hub=$matches[1]', 'top');
    add_rewrite_rule('^([^/]+)/([^/]+)/?$', 'index.php?pt24_category=$matches[1]&pt24_city=$matches[2]', 'top');
    add_rewrite_rule('^miasta/?$', 'index.php?pt24_geo=city-index', 'top');
    add_rewrite_rule('^(katowice|gliwice|zabrze|bytom|krakow|warszawa)/?$', 'index.php?pt24_city_landing=$matches[1]', 'top');
    add_rewrite_rule('^(montaz-klimatyzacji|udraznianie-kanalizacji|awaria-pradu|wymiana-dachu)/?$', 'index.php?pt24_specific_service=$matches[1]', 'top');
    add_rewrite_rule('^(montaz-klimatyzacji|udraznianie-kanalizacji|awaria-pradu|wymiana-dachu)/(katowice|gliwice|zabrze|bytom|krakow|warszawa)/?$', 'index.php?pt24_specific_service=$matches[1]&pt24_city_landing=$matches[2]', 'top');
    add_rewrite_rule('^segment-pages-sitemap\.xml$', 'index.php?pt24_segment_sitemap=1', 'top');
}
add_action('init', 'pt24_register_panel_routes');

/**
 * Add query var for custom panel routing.
 */
function pt24_add_panel_query_var($vars) {
    $vars[] = 'pt24_panel';
    $vars[] = 'pt24_service_hub';
    $vars[] = 'pt24_geo';
    $vars[] = 'pt24_city_landing';
    $vars[] = 'pt24_specific_service';
    $vars[] = 'pt24_category';
    $vars[] = 'pt24_city';
    $vars[] = 'pt24_segment_sitemap';
    return $vars;
}
add_filter('query_vars', 'pt24_add_panel_query_var');

/**
 * Reserved first URL segments that must stay under core WordPress routing.
 *
 * @param string $segment First path segment.
 * @return bool
 */
function pt24_is_reserved_route_segment($segment) {
    if (! is_string($segment) || $segment === '') {
        return false;
    }

    $segment = strtolower(sanitize_title($segment));
    $reserved = [
        'author',
        'category',
        'tag',
        'search',
        'feed',
        'blog',
        'wp-json',
        'wp-admin',
        'wp-content',
        'wp-includes',
        'index-php',
    ];

    return in_array($segment, $reserved, true);
}

/**
 * Route custom panel slugs to dedicated templates.
 */
function pt24_panel_template_include($template) {
    $specific_service = get_query_var('pt24_specific_service');
    $city_landing = get_query_var('pt24_city_landing');
    $service_category = get_query_var('pt24_category');
    $service_city = get_query_var('pt24_city');

    // Fallback routing for environments where rewrite query vars are not
    // propagated consistently (e.g. proxy/subdirectory setups).
    $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
    $request_path = (string) wp_parse_url($request_uri, PHP_URL_PATH);
    $segments = array_values(array_filter(explode('/', trim($request_path, '/'))));

    if (!empty($segments) && strtolower((string) $segments[0]) === 'pt24') {
        array_shift($segments);
    }

    if (empty($segments) && function_exists('home_url')) {
        $home_path = (string) wp_parse_url(home_url('/'), PHP_URL_PATH);
        if ($home_path && '/' !== $home_path) {
            $normalized_home_path = trim($home_path, '/');
            if ($normalized_home_path !== '') {
                $segments = array_values(array_filter(explode('/', $normalized_home_path)));
            }
        }
    }

    if (isset($segments[0]) && pt24_is_reserved_route_segment((string) $segments[0])) {
        return $template;
    }

    if (isset($segments[0]) && ! isset($segments[1]) && is_array(pt24_get_frontend_page_by_slug((string) $segments[0]))) {
        $static_template = PT24_DIR . '/static-page.php';
        if (file_exists($static_template)) {
            set_query_var('pt24_static_page', sanitize_title((string) $segments[0]));
            status_header(200);
            nocache_headers();
            return $static_template;
        }
    }

    if (isset($segments[0]) && strtolower((string) $segments[0]) === 'uslugi') {
        if (isset($segments[1]) && $segments[1] !== '') {
            set_query_var('pt24_service_hub', sanitize_title((string) $segments[1]));
        } else {
            set_query_var('pt24_service_hub', 'index');
        }

        $service_template = (isset($segments[1]) && $segments[1] !== '')
            ? PT24_DIR . '/services-single.php'
            : PT24_DIR . '/services-archive.php';

        if (file_exists($service_template)) {
            status_header(200);
            nocache_headers();
            return $service_template;
        }
    }

    if (isset($segments[0]) && strtolower((string) $segments[0]) === 'miasta' && !isset($segments[1])) {
        $cities_template = PT24_DIR . '/cities-archive.php';
        if (file_exists($cities_template)) {
            status_header(200);
            nocache_headers();
            return $cities_template;
        }
    }

    if (is_string($specific_service) && $specific_service !== '' && is_string($city_landing) && $city_landing !== '') {
        $specific_city_template = PT24_DIR . '/specific-service-city.php';
        if (file_exists($specific_city_template)) {
            status_header(200);
            nocache_headers();
            return $specific_city_template;
        }
    }

    if (is_string($service_category) && $service_category !== '' && is_string($service_city) && $service_city !== '') {
        $local_template = PT24_DIR . '/local-service-city.php';
        if (file_exists($local_template)) {
            status_header(200);
            nocache_headers();
            return $local_template;
        }
    }

    $geo = get_query_var('pt24_geo');
    if ($geo === 'city-index') {
        $cities_template = PT24_DIR . '/cities-archive.php';
        if (file_exists($cities_template)) {
            status_header(200);
            nocache_headers();
            return $cities_template;
        }
    }

    if (is_string($city_landing) && $city_landing !== '') {
        $city_template = PT24_DIR . '/city-landing.php';
        if (file_exists($city_template)) {
            status_header(200);
            nocache_headers();
            return $city_template;
        }
    }

    if (is_string($specific_service) && $specific_service !== '') {
        $specific_template = PT24_DIR . '/specific-service.php';
        if (file_exists($specific_template)) {
            status_header(200);
            nocache_headers();
            return $specific_template;
        }
    }

    $service_hub = get_query_var('pt24_service_hub');
    if (is_string($service_hub) && $service_hub !== '') {
        if ($service_hub === 'index') {
            $service_template = PT24_DIR . '/services-archive.php';
        } else {
            $service_template = PT24_DIR . '/services-single.php';
        }

        if (file_exists($service_template)) {
            status_header(200);
            nocache_headers();
            return $service_template;
        }
    }

    $panel = get_query_var('pt24_panel');
    if (! is_string($panel) || $panel === '') {
        return $template;
    }

    if ($panel === 'user') {
        $candidate = PT24_DIR . '/panel-user.php';
    } elseif ($panel === 'company') {
        $candidate = PT24_DIR . '/panel-company.php';
    } elseif ($panel === 'admin') {
        if (! current_user_can('manage_options')) {
            if (is_user_logged_in()) {
                wp_safe_redirect(home_url('/'));
            } else {
                wp_safe_redirect(wp_login_url(home_url('/admin/')));
            }
            exit;
        }
        $candidate = PT24_DIR . '/panel-admin.php';
    } else {
        $candidate = '';
    }

    if ($candidate !== '' && file_exists($candidate)) {
        status_header(200);
        nocache_headers();
        return $candidate;
    }

    return $template;
}
add_filter('template_include', 'pt24_panel_template_include', 999);

/**
 * Flush rewrite rules after route changes.
 */
function pt24_maybe_flush_panel_rewrites() {
    $version = 'panel-routes-v6';
    if (get_option('pt24_panel_routes_version') !== $version) {
        pt24_register_panel_routes();
        flush_rewrite_rules(false);
        update_option('pt24_panel_routes_version', $version, false);
    }
}
add_action('init', 'pt24_maybe_flush_panel_rewrites', 99);

/**
 * Build full URL list for segment pages (base + city variants).
 *
 * @return array<int, string>
 */
function pt24_get_segment_page_urls() {
    $urls = [];

    $base_pages = pt24_get_static_frontend_pages();
    foreach (array_keys($base_pages) as $slug) {
        if (str_ends_with((string) $slug, '-dla-firm')) {
            $urls[] = home_url('/' . sanitize_title((string) $slug) . '/');
        }
    }

    $services = pt24_get_segment_service_titles();
    $cities = pt24_get_segment_city_titles();

    foreach (array_keys($services) as $service_slug) {
        foreach (array_keys($cities) as $city_slug) {
            $urls[] = home_url('/' . $service_slug . '-dla-firm-' . $city_slug . '/');
        }
    }

    $urls = array_values(array_unique(array_map('esc_url_raw', $urls)));
    return array_values(array_filter($urls));
}

/**
 * Serve XML sitemap for segment pages.
 */
function pt24_maybe_output_segment_sitemap() {
    $is_target = ((string) get_query_var('pt24_segment_sitemap') === '1');
    if (! $is_target) {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
        $request_path = strtolower((string) wp_parse_url($request_uri, PHP_URL_PATH));
        $is_target = (rtrim($request_path, '/') === '/segment-pages-sitemap.xml');
    }

    if (! $is_target) {
        return;
    }

    $urls = pt24_get_segment_page_urls();
    $lastmod = gmdate('c');

    status_header(200);
    header('Content-Type: application/xml; charset=UTF-8');
    echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
    echo "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n";

    foreach ($urls as $url) {
        echo "  <url>\n";
        echo '    <loc>' . esc_html($url) . "</loc>\n";
        echo '    <lastmod>' . esc_html($lastmod) . "</lastmod>\n";
        echo "    <changefreq>daily</changefreq>\n";
        echo "    <priority>0.7</priority>\n";
        echo "  </url>\n";
    }

    echo "</urlset>\n";
    exit;
}
add_action('template_redirect', 'pt24_maybe_output_segment_sitemap', 0);

/**
 * Advertise segment sitemap in robots.txt.
 *
 * @param string $output Robots output.
 * @param bool   $public Is blog public.
 * @return string
 */
function pt24_append_segment_sitemap_to_robots($output, $public) {
    if (! $public) {
        return $output;
    }

    $line = 'Sitemap: ' . home_url('/segment-pages-sitemap.xml');
    if (strpos($output, $line) !== false) {
        return $output;
    }

    return rtrim((string) $output) . "\n" . $line . "\n";
}
add_filter('robots_txt', 'pt24_append_segment_sitemap_to_robots', 20, 2);

/**
 * Remove author archives from native WP sitemap on PT24.
 *
 * Author URLs are not used publicly on this installation.
 */
function pt24_filter_sitemaps_add_provider($provider, $name) {
    if ($name === 'users') {
        return false;
    }

    return $provider;
}
add_filter('wp_sitemaps_add_provider', 'pt24_filter_sitemaps_add_provider', 10, 2);

/**
 * Remove default blog taxonomies from sitemap to avoid soft-404 category URLs.
 */
function pt24_filter_sitemaps_taxonomies($taxonomies) {
    if (isset($taxonomies['category'])) {
        unset($taxonomies['category']);
    }

    if (isset($taxonomies['post_tag'])) {
        unset($taxonomies['post_tag']);
    }

    return $taxonomies;
}
add_filter('wp_sitemaps_taxonomies', 'pt24_filter_sitemaps_taxonomies');

/**
 * Force 404 status for legacy archive paths not used on PT24.
 */
function pt24_force_404_for_legacy_archives() {
    if (is_admin() || wp_doing_ajax()) {
        return;
    }

    $request_uri = isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '';
    $request_path = (string) wp_parse_url($request_uri, PHP_URL_PATH);
    $segments = array_values(array_filter(explode('/', trim($request_path, '/'))));

    if (!empty($segments) && strtolower((string) $segments[0]) === 'pt24') {
        array_shift($segments);
    }

    if (empty($segments)) {
        return;
    }

    $legacy = ['author', 'category', 'tag'];
    $first = strtolower((string) $segments[0]);

    if (! in_array($first, $legacy, true)) {
        return;
    }

    global $wp_query;
    if (isset($wp_query) && is_object($wp_query)) {
        $wp_query->set_404();
    }
    status_header(404);
    nocache_headers();
}
add_action('template_redirect', 'pt24_force_404_for_legacy_archives', 0);

/**
 * Handle company profile contact form submissions.
 */
function pt24_handle_business_contact_form() {
    $business_id = isset($_POST['business_id']) ? (int) $_POST['business_id'] : 0;
    if ($business_id <= 0) {
        wp_safe_redirect(home_url('/?contact=error'));
        exit;
    }

    $nonce = isset($_POST['pt24_contact_nonce']) ? sanitize_text_field((string) $_POST['pt24_contact_nonce']) : '';
    if (! wp_verify_nonce($nonce, 'pt24_business_contact_' . $business_id)) {
        wp_safe_redirect(get_permalink($business_id) . '?contact=error');
        exit;
    }

    $name = isset($_POST['name']) ? sanitize_text_field((string) $_POST['name']) : '';
    $email = isset($_POST['email']) ? sanitize_email((string) $_POST['email']) : '';
    $phone = isset($_POST['phone']) ? sanitize_text_field((string) $_POST['phone']) : '';
    $message = isset($_POST['message']) ? sanitize_textarea_field((string) $_POST['message']) : '';

    if ($name === '' || $email === '' || $message === '' || ! is_email($email)) {
        wp_safe_redirect(get_permalink($business_id) . '?contact=error');
        exit;
    }

    $recipient = (string) get_post_meta($business_id, 'pt24_contact_email', true);
    if ($recipient === '' || ! is_email($recipient)) {
        $author_id = (int) get_post_field('post_author', $business_id);
        $recipient = $author_id > 0 ? (string) get_the_author_meta('user_email', $author_id) : '';
    }

    if ($recipient === '' || ! is_email($recipient)) {
        $recipient = (string) get_option('admin_email');
    }

    $subject = sprintf('Nowe zapytanie do firmy: %s', get_the_title($business_id));
    $body = "Imię i nazwisko: {$name}\n";
    $body .= "Email: {$email}\n";
    $body .= "Telefon: {$phone}\n\n";
    $body .= "Wiadomość:\n{$message}\n";
    $headers = ['Reply-To: ' . $name . ' <' . $email . '>'];

    $sent = wp_mail($recipient, $subject, $body, $headers);
    wp_safe_redirect(get_permalink($business_id) . '?contact=' . ($sent ? 'sent' : 'error'));
    exit;
}
add_action('admin_post_pt24_business_contact', 'pt24_handle_business_contact_form');
add_action('admin_post_nopriv_pt24_business_contact', 'pt24_handle_business_contact_form');

/**
 * Handle service inquiry form submissions.
 */
function pt24_handle_service_inquiry_form() {
    $service_slug = isset($_POST['service_slug']) ? sanitize_title((string) $_POST['service_slug']) : '';
    if ($service_slug === '') {
        wp_safe_redirect(home_url('/uslugi/?inquiry=error'));
        exit;
    }

    $nonce = isset($_POST['pt24_service_inquiry_nonce']) ? sanitize_text_field((string) $_POST['pt24_service_inquiry_nonce']) : '';
    if (! wp_verify_nonce($nonce, 'pt24_service_inquiry_' . $service_slug)) {
        wp_safe_redirect(home_url('/uslugi/' . $service_slug . '/?inquiry=error'));
        exit;
    }

    $name = isset($_POST['name']) ? sanitize_text_field((string) $_POST['name']) : '';
    $email = isset($_POST['email']) ? sanitize_email((string) $_POST['email']) : '';
    $city = isset($_POST['city']) ? sanitize_text_field((string) $_POST['city']) : '';
    $message = isset($_POST['message']) ? sanitize_textarea_field((string) $_POST['message']) : '';

    if ($name === '' || $email === '' || $message === '' || ! is_email($email)) {
        wp_safe_redirect(home_url('/uslugi/' . $service_slug . '/?inquiry=error'));
        exit;
    }

    $service_term = get_term_by('slug', $service_slug, 'pt24_service_cat');
    $service_name = is_object($service_term) && isset($service_term->name)
        ? (string) $service_term->name
        : ucfirst(str_replace('-', ' ', $service_slug));

    $subject = sprintf('Nowe zapytanie o usługę: %s', $service_name);
    $body = "Imię i nazwisko: {$name}\n";
    $body .= "Email: {$email}\n";
    $body .= "Miasto: {$city}\n\n";
    $body .= "Wiadomość:\n{$message}\n";
    $headers = ['Reply-To: ' . $name . ' <' . $email . '>'];

    $sent = wp_mail((string) get_option('admin_email'), $subject, $body, $headers);

    wp_safe_redirect(home_url('/uslugi/' . $service_slug . '/?inquiry=' . ($sent ? 'sent' : 'error')));
    exit;
}
add_action('admin_post_pt24_service_inquiry', 'pt24_handle_service_inquiry_form');
add_action('admin_post_nopriv_pt24_service_inquiry', 'pt24_handle_service_inquiry_form');

/**
 * Insert marketing lead from frontend segment pages.
 *
 * @param array<string, string> $payload Lead payload.
 * @return bool
 */
function pt24_insert_marketing_lead($payload) {
    global $wpdb;

    $leads_table = pt24_resolve_table_name('pt24_leads');
    if (! pt24_table_exists($leads_table)) {
        return false;
    }

    if (function_exists('pt24_ensure_leads_billing_columns')) {
        pt24_ensure_leads_billing_columns($leads_table);
    }

    $columns = (array) $wpdb->get_col("SHOW COLUMNS FROM {$leads_table}", 0);
    if (empty($columns)) {
        return false;
    }

    $name = isset($payload['name']) ? sanitize_text_field((string) $payload['name']) : '';
    $email = isset($payload['email']) ? sanitize_email((string) $payload['email']) : '';
    $phone = isset($payload['phone']) ? sanitize_text_field((string) $payload['phone']) : '';
    $city = isset($payload['city']) ? sanitize_text_field((string) $payload['city']) : '';
    $service_slug = isset($payload['service_slug']) ? sanitize_title((string) $payload['service_slug']) : '';
    $page_slug = isset($payload['page_slug']) ? sanitize_title((string) $payload['page_slug']) : '';
    $message = isset($payload['message']) ? sanitize_textarea_field((string) $payload['message']) : '';
    $source = 'segment-form:' . $page_slug;

    $metadata = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'city' => $city,
        'service_slug' => $service_slug,
        'page_slug' => $page_slug,
        'source' => $source,
    ];

    $data = [];
    $formats = [];

    // LeadAI schema variant.
    if (in_array('category', $columns, true)) {
        $location = sanitize_title($city !== '' ? $city : 'polska');

        if (in_array('category', $columns, true)) {
            $data['category'] = $service_slug !== '' ? $service_slug : 'ogolne';
            $formats[] = '%s';
        }
        if (in_array('location', $columns, true)) {
            $data['location'] = $location;
            $formats[] = '%s';
        }
        if (in_array('message', $columns, true)) {
            $data['message'] = $message;
            $formats[] = '%s';
        }
        if (in_array('status', $columns, true)) {
            $data['status'] = 'NEW';
            $formats[] = '%s';
        }
        if (in_array('score', $columns, true)) {
            $data['score'] = 0;
            $formats[] = '%d';
        }
        if (in_array('urgency', $columns, true)) {
            $data['urgency'] = 'MEDIUM';
            $formats[] = '%s';
        }
        if (in_array('package_type', $columns, true)) {
            $data['package_type'] = 'FREE';
            $formats[] = '%s';
        }
        if (in_array('created_at', $columns, true)) {
            $data['created_at'] = time();
            $formats[] = '%d';
        }
        if (in_array('metadata', $columns, true)) {
            $data['metadata'] = wp_json_encode($metadata);
            $formats[] = '%s';
        }
    } else {
        // Classic schema variant.
        if (in_array('name', $columns, true)) {
            $data['name'] = $name;
            $formats[] = '%s';
        }
        if (in_array('email', $columns, true)) {
            $data['email'] = $email;
            $formats[] = '%s';
        }
        if (in_array('phone', $columns, true)) {
            $data['phone'] = $phone;
            $formats[] = '%s';
        }
        if (in_array('city', $columns, true)) {
            $data['city'] = $city;
            $formats[] = '%s';
        }
        if (in_array('service', $columns, true)) {
            $data['service'] = $service_slug;
            $formats[] = '%s';
        }
        if (in_array('message', $columns, true)) {
            $data['message'] = $message;
            $formats[] = '%s';
        }
        if (in_array('source', $columns, true)) {
            $data['source'] = $source;
            $formats[] = '%s';
        }
        if (in_array('status', $columns, true)) {
            $data['status'] = 'new';
            $formats[] = '%s';
        }
        if (in_array('created_at', $columns, true)) {
            $data['created_at'] = current_time('mysql');
            $formats[] = '%s';
        }
        if (in_array('updated_at', $columns, true)) {
            $data['updated_at'] = current_time('mysql');
            $formats[] = '%s';
        }
        if (in_array('metadata', $columns, true)) {
            $data['metadata'] = wp_json_encode($metadata);
            $formats[] = '%s';
        }
    }

    if (empty($data)) {
        return false;
    }

    return $wpdb->insert($leads_table, $data, $formats) !== false;
}

/**
 * Handle marketing lead form from static segment pages.
 */
function pt24_handle_marketing_lead_form() {
    $redirect = isset($_POST['redirect_to']) ? esc_url_raw((string) wp_unslash($_POST['redirect_to'])) : '';
    if ($redirect === '') {
        $redirect = wp_get_referer();
    }
    if (! is_string($redirect) || $redirect === '') {
        $redirect = home_url('/kontakt/');
    }
    $redirect = wp_validate_redirect($redirect, home_url('/kontakt/'));
    $redirect = remove_query_arg('form', $redirect);

    $nonce = isset($_POST['pt24_marketing_lead_nonce']) ? sanitize_text_field((string) $_POST['pt24_marketing_lead_nonce']) : '';
    if (! wp_verify_nonce($nonce, 'pt24_marketing_lead')) {
        wp_safe_redirect(add_query_arg('form', 'error', $redirect));
        exit;
    }

    $name = isset($_POST['name']) ? sanitize_text_field((string) $_POST['name']) : '';
    $email = isset($_POST['email']) ? sanitize_email((string) $_POST['email']) : '';
    $message = isset($_POST['message']) ? sanitize_textarea_field((string) $_POST['message']) : '';

    if ($name === '' || $email === '' || $message === '' || ! is_email($email)) {
        wp_safe_redirect(add_query_arg('form', 'error', $redirect));
        exit;
    }

    $phone = isset($_POST['phone']) ? sanitize_text_field((string) $_POST['phone']) : '';
    $city = isset($_POST['city']) ? sanitize_text_field((string) $_POST['city']) : '';
    $service_slug = isset($_POST['service_slug']) ? sanitize_title((string) $_POST['service_slug']) : '';
    $page_slug = isset($_POST['page_slug']) ? sanitize_title((string) $_POST['page_slug']) : '';

    $inserted = pt24_insert_marketing_lead([
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'city' => $city,
        'service_slug' => $service_slug,
        'page_slug' => $page_slug,
        'message' => $message,
    ]);

    if ($inserted) {
        $notify_enabled = get_option('pt24_notify_enabled', '1');
        if ($notify_enabled !== '0') {
            $notify_email = (string) get_option('pt24_notify_email', '');
            if ($notify_email === '' || ! is_email($notify_email)) {
                $notify_email = (string) get_option('admin_email');
            }

            $service_label = $service_slug !== ''
                ? ucfirst(str_replace('-', ' ', $service_slug))
                : 'nieokreslonej uslugi';

            $subject = sprintf('[PT24] Nowe zapytanie z formularza segmentowego — %s', $service_label . ($city !== '' ? ' / ' . $city : ''));
            $body  = "Imie i nazwisko: {$name}\n";
            $body .= "Email: {$email}\n";
            $body .= "Telefon: {$phone}\n";
            $body .= "Miasto: {$city}\n";
            $body .= "Usluga: {$service_label}\n";
            $body .= "Strona: {$page_slug}\n\n";
            $body .= "Tresc:\n{$message}\n";
            $headers = [
                'Reply-To: ' . $name . ' <' . $email . '>',
                'Content-Type: text/plain; charset=UTF-8',
            ];

            wp_mail($notify_email, $subject, $body, $headers);
        }
    }

    wp_safe_redirect(add_query_arg('form', $inserted ? 'sent' : 'error', $redirect));
    exit;
}
add_action('admin_post_pt24_marketing_lead', 'pt24_handle_marketing_lead_form');
add_action('admin_post_nopriv_pt24_marketing_lead', 'pt24_handle_marketing_lead_form');

/**
 * Allow public PT24 host in wp_safe_redirect targets.
 *
 * @param string[] $hosts Allowed hosts.
 * @return string[]
 */
function pt24_allow_public_redirect_hosts($hosts) {
    $hosts = is_array($hosts) ? $hosts : [];
    $hosts[] = 'pt24.pro';
    return array_values(array_unique(array_filter($hosts)));
}
add_filter('allowed_redirect_hosts', 'pt24_allow_public_redirect_hosts');

/**
 * Lead pricing matrix for core PT24 service categories.
 *
 * @return array<string, array{label:string,min:int,max:int}>
 */
function pt24_get_lead_pricing_matrix() {
    return [
        'hydraulik' => ['label' => 'Hydraulik', 'min' => 20, 'max' => 40],
        'elektryk' => ['label' => 'Elektryk', 'min' => 25, 'max' => 50],
        'mechanik' => ['label' => 'Mechanik', 'min' => 20, 'max' => 60],
        'remont' => ['label' => 'Remont', 'min' => 50, 'max' => 150],
        'dach' => ['label' => 'Dach', 'min' => 80, 'max' => 250],
        'pompy-ciepla' => ['label' => 'Pompy ciepla', 'min' => 150, 'max' => 350],
        'fotowoltaika' => ['label' => 'Fotowoltaika', 'min' => 100, 'max' => 300],
    ];
}

/**
 * SaaS subscription plans for PT24 companies.
 *
 * @return array<string, array{name:string,price:int,period:string,features:string[]}>
 */
function pt24_get_subscription_plans() {
    return [
        'free' => [
            'name' => 'Free',
            'price' => 0,
            'period' => 'monthly',
            'features' => [
                'Profil firmy',
                'Opinie',
                'Podstawowa widocznosc',
            ],
        ],
        'starter' => [
            'name' => 'Starter',
            'price' => 49,
            'period' => 'monthly',
            'features' => [
                'Wiecej zdjec',
                'Statystyki',
                'Podstawowy CRM',
            ],
        ],
        'pro' => [
            'name' => 'Pro',
            'price' => 149,
            'period' => 'monthly',
            'features' => [
                'Wiecej leadow',
                'Priorytet',
                'AI',
                'Kalendarz',
            ],
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'price' => 499,
            'period' => 'monthly',
            'features' => [
                'Wiele oddzialow',
                'API',
                'Wielu pracownikow',
                'Integracje CRM',
            ],
        ],
    ];
}

/**
 * Premium listing upsells.
 *
 * @return array<string, array{label:string,price:int,period:string}>
 */
function pt24_get_premium_listing_packages() {
    return [
        'top3' => ['label' => 'TOP 3', 'price' => 299, 'period' => 'monthly'],
        'sponsored' => ['label' => 'Sponsorowana firma', 'price' => 199, 'period' => 'monthly'],
        'recommended' => ['label' => 'Polecana firma', 'price' => 149, 'period' => 'monthly'],
        'badge' => ['label' => 'Premium Badge', 'price' => 79, 'period' => 'monthly'],
        'highlight' => ['label' => 'Kolorowe wyroznienie', 'price' => 99, 'period' => 'monthly'],
    ];
}

/**
 * Lead credit packages for one-off purchases.
 *
 * @return array<string, array{label:string,credits:int,price:int,currency:string}>
 */
function pt24_get_lead_credit_packages() {
    return [
        'pack_25' => ['label' => 'Pakiet 25 leadow', 'credits' => 25, 'price' => 399, 'currency' => 'PLN'],
        'pack_60' => ['label' => 'Pakiet 60 leadow', 'credits' => 60, 'price' => 899, 'currency' => 'PLN'],
        'pack_150' => ['label' => 'Pakiet 150 leadow', 'credits' => 150, 'price' => 1999, 'currency' => 'PLN'],
    ];
}

/**
 * Included monthly lead credits by subscription plan.
 *
 * @return array<string, int>
 */
function pt24_get_plan_lead_allowances() {
    return [
        'free' => 0,
        'starter' => 10,
        'pro' => 40,
        'enterprise' => 160,
    ];
}

/**
 * Get monetization state for a company user.
 *
 * @param int $user_id User ID.
 * @return array{plan:string,credits:int,included:int}
 */
function pt24_get_company_monetization_state($user_id) {
    $user_id = (int) $user_id;
    $plans = pt24_get_subscription_plans();
    $allowances = pt24_get_plan_lead_allowances();

    $plan = sanitize_key((string) get_user_meta($user_id, 'pt24_company_plan', true));
    if ($plan === '' || ! isset($plans[$plan])) {
        $plan = 'free';
    }

    $included = (int) get_user_meta($user_id, 'pt24_company_plan_included_leads', true);
    if ($included <= 0 && isset($allowances[$plan])) {
        $included = (int) $allowances[$plan];
    }

    $credits = (int) get_user_meta($user_id, 'pt24_company_lead_credits', true);
    if ($credits < 0) {
        $credits = 0;
    }

    return [
        'plan' => $plan,
        'credits' => $credits,
        'included' => $included,
    ];
}

/**
 * Persist monetization state for company user.
 *
 * @param int    $user_id  User ID.
 * @param string $plan     Plan slug.
 * @param int    $credits  Lead credits.
 * @param int    $included Included monthly leads.
 */
function pt24_set_company_monetization_state($user_id, $plan, $credits, $included) {
    $user_id = (int) $user_id;
    update_user_meta($user_id, 'pt24_company_plan', sanitize_key((string) $plan));
    update_user_meta($user_id, 'pt24_company_lead_credits', max(0, (int) $credits));
    update_user_meta($user_id, 'pt24_company_plan_included_leads', max(0, (int) $included));
}

/**
 * Append billing event to company history.
 *
 * @param int   $user_id User ID.
 * @param array $entry   Billing event payload.
 */
function pt24_append_company_billing_history($user_id, $entry) {
    $user_id = (int) $user_id;
    if ($user_id <= 0 || ! is_array($entry)) {
        return;
    }

    $history = get_user_meta($user_id, 'pt24_company_billing_history', true);
    if (! is_array($history)) {
        $history = [];
    }

    $entry['created_at'] = current_time('mysql');
    array_unshift($history, $entry);
    $history = array_slice($history, 0, 60);
    update_user_meta($user_id, 'pt24_company_billing_history', $history);
}

/**
 * Consume one lead credit for company user.
 *
 * @param int $user_id User ID.
 * @param int $lead_id Lead ID.
 * @return bool
 */
function pt24_consume_company_lead_credit($user_id, $lead_id = 0) {
    $user_id = (int) $user_id;
    if ($user_id <= 0) {
        return false;
    }

    $state = pt24_get_company_monetization_state($user_id);
    $credits = (int) $state['credits'];

    if ($credits <= 0) {
        pt24_append_company_billing_history($user_id, [
            'type' => 'lead_overdraft',
            'lead_id' => (int) $lead_id,
            'plan' => (string) $state['plan'],
            'credits_after' => 0,
            'amount' => 0,
            'currency' => 'PLN',
        ]);
        return false;
    }

    $credits--;
    pt24_set_company_monetization_state($user_id, (string) $state['plan'], $credits, (int) $state['included']);

    pt24_append_company_billing_history($user_id, [
        'type' => 'lead_consumed',
        'lead_id' => (int) $lead_id,
        'plan' => (string) $state['plan'],
        'credits_after' => $credits,
        'amount' => 1,
        'currency' => 'CREDIT',
    ]);

    return true;
}

/**
 * Parse JSON lead metadata.
 *
 * @param string|null $raw Raw metadata string.
 * @return array
 */
function pt24_parse_lead_metadata($raw) {
    $raw = is_string($raw) ? trim($raw) : '';
    if ($raw === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        return $decoded;
    }

    return ['legacy_meta' => $raw];
}

/**
 * Resolve PT24 table name across prefix conventions.
 *
 * Supports both `{prefix}pt24_*` and `{prefix}*` variants used by some installs.
 *
 * @param string $logical_name Logical table suffix, e.g. `pt24_leads`.
 * @return string
 */
function pt24_resolve_table_name($logical_name) {
    static $cache = [];

    $logical_name = sanitize_key((string) $logical_name);
    if ($logical_name === '') {
        return '';
    }

    if (isset($cache[$logical_name])) {
        return $cache[$logical_name];
    }

    global $wpdb;
    $candidates = [
        strpos($logical_name, 'pt24_') === 0 ? $wpdb->prefix . substr($logical_name, 5) : '',
        $wpdb->prefix . $logical_name,
        $wpdb->base_prefix . $logical_name,
    ];

    $candidates = array_values(array_unique(array_filter($candidates)));
    $existing_candidates = [];

    foreach ($candidates as $table_name) {
        $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name));
        if ($exists === $table_name) {
            $existing_candidates[] = $table_name;
        }
    }

    if (! empty($existing_candidates)) {
        $required_columns = [];
        if ($logical_name === 'pt24_leads') {
            $required_columns = ['assigned_contractor_id'];
        } elseif ($logical_name === 'pt24_contractors') {
            $required_columns = ['id', 'email'];
        }

        if (! empty($required_columns)) {
            foreach ($existing_candidates as $table_name) {
                $columns = $wpdb->get_col("SHOW COLUMNS FROM {$table_name}", 0);
                $has_all_required = true;
                foreach ($required_columns as $required) {
                    if (! in_array($required, (array) $columns, true)) {
                        $has_all_required = false;
                        break;
                    }
                }
                if ($has_all_required) {
                    $cache[$logical_name] = $table_name;
                    return $table_name;
                }
            }
        }

        $cache[$logical_name] = $existing_candidates[0];
        return $cache[$logical_name];
    }

    $cache[$logical_name] = $candidates[0];
    return $cache[$logical_name];
}

/**
 * Check whether a table exists.
 *
 * @param string $table_name Table name.
 * @return bool
 */
function pt24_table_exists($table_name) {
    global $wpdb;
    $table_name = (string) $table_name;
    if ($table_name === '') {
        return false;
    }

    $exists = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table_name));
    return $exists === $table_name;
}

/**
 * Create contractors table when missing.
 *
 * @param string $table_name Table name.
 */
function pt24_ensure_contractors_table($table_name) {
    if ($table_name === '' || pt24_table_exists($table_name)) {
        return;
    }

    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        phone VARCHAR(20) DEFAULT NULL,
        package_type VARCHAR(20) DEFAULT 'FREE',
        status VARCHAR(20) DEFAULT 'active',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        metadata TEXT DEFAULT NULL,
        PRIMARY KEY (id),
        KEY email (email),
        KEY status (status)
    ) {$charset_collate};";
    dbDelta($sql);
}

/**
 * Ensure leads table contains columns required for billing sync.
 *
 * @param string $table_name Leads table name.
 */
function pt24_ensure_leads_billing_columns($table_name) {
    if ($table_name === '' || ! pt24_table_exists($table_name)) {
        return;
    }

    global $wpdb;
    $columns = (array) $wpdb->get_col("SHOW COLUMNS FROM {$table_name}", 0);

    if (! in_array('assigned_contractor_id', $columns, true)) {
        $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN assigned_contractor_id BIGINT UNSIGNED DEFAULT NULL");
        $wpdb->query("ALTER TABLE {$table_name} ADD KEY assigned_contractor_id (assigned_contractor_id)");
    }

    if (! in_array('metadata', $columns, true)) {
        $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN metadata LONGTEXT DEFAULT NULL");
    }
}

/**
 * Sync unbilled assigned leads and consume credits once per lead.
 */
function pt24_sync_lead_billing_events() {
    static $processed = false;
    if ($processed) {
        return;
    }
    $processed = true;

    if (is_admin() && ! wp_doing_ajax()) {
        return;
    }

    global $wpdb;
    $leads_table = pt24_resolve_table_name('pt24_leads');
    $contractors_table = pt24_resolve_table_name('pt24_contractors');

    if (! pt24_table_exists($leads_table)) {
        return;
    }

    pt24_ensure_leads_billing_columns($leads_table);
    $lead_columns = (array) $wpdb->get_col("SHOW COLUMNS FROM {$leads_table}", 0);
    if (! in_array('assigned_contractor_id', $lead_columns, true) || ! in_array('metadata', $lead_columns, true)) {
        return;
    }

    pt24_ensure_contractors_table($contractors_table);
    if (! pt24_table_exists($contractors_table)) {
        return;
    }

    $rows = $wpdb->get_results(
        "SELECT l.id, l.assigned_contractor_id, l.metadata, c.email
        FROM {$leads_table} l
        LEFT JOIN {$contractors_table} c ON c.id = l.assigned_contractor_id
        WHERE l.assigned_contractor_id IS NOT NULL
          AND (
            l.metadata IS NULL
            OR l.metadata = ''
            OR l.metadata NOT LIKE '%\\\"billing_processed\\\":1%'
          )
        ORDER BY l.id ASC
        LIMIT 25"
    );

    if (! is_array($rows) || empty($rows)) {
        return;
    }

    foreach ($rows as $row) {
        $lead_id = isset($row->id) ? (int) $row->id : 0;
        $email = isset($row->email) ? sanitize_email((string) $row->email) : '';
        $meta = pt24_parse_lead_metadata(isset($row->metadata) ? (string) $row->metadata : '');

        if ($lead_id <= 0) {
            continue;
        }

        $user = $email !== '' ? get_user_by('email', $email) : false;
        if (! $user || ! isset($user->ID)) {
            $meta['billing_processed'] = 1;
            $meta['billing_charged'] = 0;
            $meta['billing_note'] = 'missing_user';
            $meta['billing_at'] = current_time('mysql');

            $wpdb->update(
                $leads_table,
                ['metadata' => wp_json_encode($meta)],
                ['id' => $lead_id],
                ['%s'],
                ['%d']
            );
            continue;
        }

        $charged = pt24_consume_company_lead_credit((int) $user->ID, $lead_id);
        $meta['billing_processed'] = 1;
        $meta['billing_charged'] = $charged ? 1 : 0;
        $meta['billing_user_id'] = (int) $user->ID;
        $meta['billing_at'] = current_time('mysql');

        $wpdb->update(
            $leads_table,
            ['metadata' => wp_json_encode($meta)],
            ['id' => $lead_id],
            ['%s'],
            ['%d']
        );
    }
}
add_action('init', 'pt24_sync_lead_billing_events', 30);

/**
 * Handle company plan change from panel.
 */
function pt24_handle_company_plan_change() {
    if (! is_user_logged_in()) {
        wp_safe_redirect(wp_login_url(home_url('/panel-firmy/')));
        exit;
    }

    $user_id = get_current_user_id();
    $nonce = isset($_POST['pt24_company_plan_nonce']) ? sanitize_text_field((string) $_POST['pt24_company_plan_nonce']) : '';
    if (! wp_verify_nonce($nonce, 'pt24_company_plan_' . $user_id)) {
        wp_safe_redirect(home_url('/panel-firmy/?billing=error'));
        exit;
    }

    $plans = pt24_get_subscription_plans();
    $allowances = pt24_get_plan_lead_allowances();
    $next_plan = isset($_POST['plan']) ? sanitize_key((string) $_POST['plan']) : 'free';

    if (! isset($plans[$next_plan])) {
        wp_safe_redirect(home_url('/panel-firmy/?billing=invalid-plan'));
        exit;
    }

    $state = pt24_get_company_monetization_state($user_id);
    $included = isset($allowances[$next_plan]) ? (int) $allowances[$next_plan] : 0;
    $credits = max((int) $state['credits'], $included);

    pt24_set_company_monetization_state($user_id, $next_plan, $credits, $included);
    pt24_append_company_billing_history($user_id, [
        'type' => 'plan_changed',
        'plan' => $next_plan,
        'credits_after' => $credits,
        'amount' => isset($plans[$next_plan]['price']) ? (int) $plans[$next_plan]['price'] : 0,
        'currency' => 'PLN',
    ]);
    wp_safe_redirect(home_url('/panel-firmy/?billing=plan-updated'));
    exit;
}
add_action('admin_post_pt24_company_change_plan', 'pt24_handle_company_plan_change');

/**
 * Handle lead credit package purchase from panel.
 */
function pt24_handle_company_buy_lead_pack() {
    if (! is_user_logged_in()) {
        wp_safe_redirect(wp_login_url(home_url('/panel-firmy/')));
        exit;
    }

    $user_id = get_current_user_id();
    $nonce = isset($_POST['pt24_company_pack_nonce']) ? sanitize_text_field((string) $_POST['pt24_company_pack_nonce']) : '';
    if (! wp_verify_nonce($nonce, 'pt24_company_pack_' . $user_id)) {
        wp_safe_redirect(home_url('/panel-firmy/?billing=error'));
        exit;
    }

    $packages = pt24_get_lead_credit_packages();
    $pack_key = isset($_POST['pack']) ? sanitize_key((string) $_POST['pack']) : '';

    if (! isset($packages[$pack_key])) {
        wp_safe_redirect(home_url('/panel-firmy/?billing=invalid-pack'));
        exit;
    }

    $state = pt24_get_company_monetization_state($user_id);
    $credits = (int) $state['credits'] + (int) $packages[$pack_key]['credits'];

    pt24_set_company_monetization_state($user_id, (string) $state['plan'], $credits, (int) $state['included']);
    pt24_append_company_billing_history($user_id, [
        'type' => 'credits_purchased',
        'plan' => (string) $state['plan'],
        'pack' => $pack_key,
        'credits_added' => (int) $packages[$pack_key]['credits'],
        'credits_after' => $credits,
        'amount' => (int) $packages[$pack_key]['price'],
        'currency' => (string) $packages[$pack_key]['currency'],
    ]);
    wp_safe_redirect(home_url('/panel-firmy/?billing=credits-added'));
    exit;
}
add_action('admin_post_pt24_company_buy_lead_pack', 'pt24_handle_company_buy_lead_pack');

/**
 * Calculate dynamic lead price for a service slug.
 *
 * @param string $service_slug Service slug.
 * @param string $quality      Lead quality: standard|high|exclusive.
 * @return array{service_slug:string,min:int,max:int,suggested:int,currency:string,quality:string}
 */
function pt24_calculate_lead_price($service_slug, $quality = 'standard') {
    $service_slug = sanitize_title((string) $service_slug);
    $quality = sanitize_key((string) $quality);

    $matrix = pt24_get_lead_pricing_matrix();
    $range = isset($matrix[$service_slug]) ? $matrix[$service_slug] : ['min' => 20, 'max' => 40];

    $multiplier = 1.0;
    if ($quality === 'high') {
        $multiplier = 1.2;
    } elseif ($quality === 'exclusive') {
        $multiplier = 1.5;
    }

    $min = (int) round(((int) $range['min']) * $multiplier);
    $max = (int) round(((int) $range['max']) * $multiplier);
    $suggested = (int) round(($min + $max) / 2);

    return [
        'service_slug' => $service_slug,
        'min' => $min,
        'max' => $max,
        'suggested' => $suggested,
        'currency' => 'PLN',
        'quality' => $quality,
    ];
}

/**
 * REST: return monetization config and dynamic lead pricing.
 */
function pt24_register_monetization_rest_routes() {
    register_rest_route('pt24/v1', '/monetization', [
        'methods' => 'GET',
        'permission_callback' => '__return_true',
        'callback' => function (WP_REST_Request $request) {
            $service = sanitize_title((string) $request->get_param('service'));
            $quality = sanitize_key((string) $request->get_param('quality'));

            if ($quality === '') {
                $quality = 'standard';
            }

            return [
                'leadPricing' => pt24_get_lead_pricing_matrix(),
                'plans' => pt24_get_subscription_plans(),
                'premiumListings' => pt24_get_premium_listing_packages(),
                'leadCreditPackages' => pt24_get_lead_credit_packages(),
                'dynamicLeadPrice' => pt24_calculate_lead_price($service, $quality),
                'revenueMixTarget' => [
                    'leadEngine' => 40,
                    'saas' => 20,
                    'premiumProfiles' => 10,
                    'adsense' => 8,
                    'affiliate' => 7,
                    'aiPremium' => 7,
                    'marketingServices' => 5,
                    'apiWhiteLabel' => 3,
                ],
            ];
        },
    ]);
}
add_action('rest_api_init', 'pt24_register_monetization_rest_routes');
