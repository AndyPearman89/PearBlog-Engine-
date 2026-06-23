<?php
/**
 * Plugin Name: PT24.PRO Content Seed
 * Description: One-time, idempotent content seed for PT24.PRO — real pages
 *              (no placeholders), service/city landing pages, primary & footer
 *              menus and a static front page. FTP-only friendly: runs itself on
 *              `init` and is guarded so it executes only on the pt24 host and
 *              only once per version.
 * Version:     1.0.0
 * Author:      PearBlog Engine
 *
 * @package PT24
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

const PT24_SEED_VERSION = '1.8.0';

/**
 * Whether the current site is the PT24 install.
 *
 * The install lives at home_url() = https://wordpress2614653.home.pl/pt24, i.e.
 * the 'pt24' marker is in the URL path. Matching the full home_url string is
 * stable regardless of the proxy/Cloudflare Host header.
 */
function pt24_seed_is_pt24(): bool {
    $url = function_exists( 'home_url' ) ? (string) home_url( '/' ) : (string) ( $_SERVER['HTTP_HOST'] ?? '' );
    return false !== stripos( $url, 'pt24' );
}

/**
 * Create (or fetch) a published page by slug with full content. Idempotent.
 *
 * @return int Page ID (0 on failure).
 */
function pt24_seed_page( string $slug, string $title, string $content ): int {
    $existing = get_page_by_path( $slug );
    if ( $existing instanceof WP_Post ) {
        // Adopt the page as a canonical PT24 page: refresh title/content and clear
        // any leftover page template (e.g. a poradnik.pro contact template) that
        // would otherwise hijack rendering of the seeded content.
        wp_update_post(
            array(
                'ID'           => $existing->ID,
                'post_title'   => $title,
                'post_content' => $content,
            )
        );
        update_post_meta( $existing->ID, '_pt24_seeded', PT24_SEED_VERSION );
        update_post_meta( $existing->ID, '_wp_page_template', 'default' );
        return (int) $existing->ID;
    }

    $page_id = wp_insert_post(
        array(
            'post_title'   => $title,
            'post_name'    => $slug,
            'post_content' => $content,
            'post_status'  => 'publish',
            'post_type'    => 'page',
        )
    );

    if ( is_wp_error( $page_id ) || ! $page_id ) {
        return 0;
    }

    update_post_meta( $page_id, '_pt24_seeded', PT24_SEED_VERSION );
    update_post_meta( $page_id, '_wp_page_template', 'default' );
    return (int) $page_id;
}

/**
 * Build the homepage HTML (service x city grid + value props + CTA).
 */
function pt24_seed_home_content(): string {
    $services = array(
        'hydraulik'        => array( 'Hydraulik', 'droplet', 'Awarie, montaż i wymiana instalacji wodno-kanalizacyjnej oraz armatury.' ),
        'elektryk'         => array( 'Elektryk', 'zap', 'Instalacje, pomiary, usuwanie usterek i montaż osprzętu — z uprawnieniami.' ),
        'mechanik'         => array( 'Mechanik', 'wrench', 'Diagnostyka, naprawy bieżące i przeglądy — także z dojazdem do klienta.' ),
        'pompa-ciepla'     => array( 'Pompa ciepła', 'thermometer', 'Dobór, montaż i serwis pomp ciepła oraz nowoczesnych systemów grzewczych.' ),
        'remont-lazienki'  => array( 'Remont łazienki', 'home', 'Kompleksowe wykończenia — od hydrauliki i glazury po biały montaż.' ),
        'fotowoltaika'     => array( 'Fotowoltaika', 'grid', 'Projekt, montaż i uruchomienie instalacji PV wraz z formalnościami.' ),
    );
    $cities = array(
        'warszawa' => 'Warszawa',
        'krakow'   => 'Kraków',
        'wroclaw'  => 'Wrocław',
        'poznan'   => 'Poznań',
        'gdansk'   => 'Gdańsk',
        'katowice' => 'Katowice',
    );

    $html  = '<div class="pt24-home pt24-page">';
    $html .= '<section class="pt24-home__hero">';
    $html .= '<span class="pt24-home__badge">⚡ Pomoc techniczna 24h — fachowcy w całej Polsce</span>';
    $html .= '<h1>Znajdź sprawdzonego fachowca w swojej okolicy</h1>';
    $html .= '<p>Opisz zlecenie raz i otrzymaj do trzech bezpłatnych ofert od lokalnych specjalistów. Bez prowizji, bez zobowiązań.</p>';
    $html .= '<div class="pt24-home__hero-cta">';
    $html .= '<a class="pt24-btn pt24-btn--primary" href="' . esc_url( home_url( '/jak-to-dziala/' ) ) . '">Zamów bezpłatną wycenę</a>';
    $html .= '<a class="pt24-btn pt24-btn--ghost-light" href="#uslugi">Przeglądaj usługi</a>';
    $html .= '</div>';
    $html .= '<ul class="pt24-home__stats">';
    $html .= '<li><strong>6</strong> miast w Polsce</li>';
    $html .= '<li><strong>6</strong> kategorii usług</li>';
    $html .= '<li><strong>do 24 h</strong> pierwsza oferta</li>';
    $html .= '<li><strong>0 zł</strong> dla zlecających</li>';
    $html .= '</ul>';
    $html .= '</section>';

    // How it works.
    $html .= '<section class="pt24-home__how"><h2>Jak to działa?</h2>';
    $html .= '<p class="pt24-home__section-intro">Trzy proste kroki dzielą Cię od realizacji zlecenia — bez dzwonienia po kilkunastu numerach.</p>';
    $html .= '<ol class="pt24-flow">';
    $html .= '<li><strong>Opisz zlecenie</strong>Wybierz usługę i miasto, a następnie krótko opisz zakres prac. Zajmuje to mniej niż minutę.</li>';
    $html .= '<li><strong>Otrzymaj oferty</strong>Twoje zapytanie trafia do zweryfikowanych fachowców z okolicy, którzy przesyłają wyceny.</li>';
    $html .= '<li><strong>Wybierz i zrealizuj</strong>Porównujesz ceny, opinie i terminy, po czym umawiasz się bezpośrednio z wybranym specjalistą.</li>';
    $html .= '</ol></section>';

    // Services with icons + descriptions.
    $html .= '<section class="pt24-home__services" id="uslugi"><h2>Usługi, które zamówisz w PT24</h2>';
    $html .= '<p class="pt24-home__section-intro">Sześć kategorii najczęściej poszukiwanych specjalistów — w sześciu największych miastach w Polsce.</p>';
    $html .= '<div class="pt24-home__cards">';
    foreach ( $services as $sslug => $sdata ) {
        list( $sname, $sicon, $sdesc ) = $sdata;
        $links = array();
        foreach ( $cities as $cslug => $cname ) {
            $links[] = sprintf( '<a href="%s">%s</a>', esc_url( home_url( "/{$cslug}/{$sslug}/" ) ), esc_html( $cname ) );
        }
        $html .= '<div class="pt24-home__card">'
            . '<span class="pt24-home__card-icon"><span class="pt24-ico pt24-ico--' . esc_attr( $sicon ) . '" aria-hidden="true"></span></span>'
            . '<h3>' . esc_html( $sname ) . '</h3>'
            . '<p class="pt24-home__card-desc">' . esc_html( $sdesc ) . '</p>'
            . '<p class="pt24-home__card-cities">' . implode( ' · ', $links ) . '</p>'
            . '</div>';
    }
    $html .= '</div></section>';

    // Why PT24 — feature grid with icons.
    $features = array(
        array( 'shield', 'Zweryfikowani fachowcy', 'Profile, opinie i oceny realnych klientów pomagają wybrać pewnego wykonawcę.' ),
        array( 'tag', 'Bezpłatna wycena', 'Porównujesz konkretne oferty cenowe bez żadnych kosztów i zobowiązań.' ),
        array( 'clock', 'Szybki kontakt', 'Pierwsze oferty otrzymujesz zwykle w kilka godzin od zgłoszenia.' ),
        array( 'pin', 'Lokalni specjaliści', 'Łączymy Cię z fachowcami, którzy realnie działają w Twojej okolicy.' ),
        array( 'star', 'Jakość i opinie', 'Oceny i komentarze klientów premiują rzetelnych wykonawców.' ),
        array( 'lock', 'Bezpieczeństwo danych', 'Dane przetwarzamy zgodnie z RODO i przekazujemy tylko wybranym firmom.' ),
    );
    $html .= '<section class="pt24-home__values"><h2>Dlaczego warto wybrać PT24?</h2>';
    $html .= '<ul class="pt24-home__features">';
    foreach ( $features as $f ) {
        $html .= '<li><span class="pt24-home__feature-ico"><span class="pt24-ico pt24-ico--' . esc_attr( $f[0] ) . '" aria-hidden="true"></span></span>'
            . '<div><strong>' . esc_html( $f[1] ) . '</strong><p>' . esc_html( $f[2] ) . '</p></div></li>';
    }
    $html .= '</ul></section>';

    // Coverage / cities.
    $html .= '<section class="pt24-home__cities"><h2>Działamy w Twoim mieście</h2>';
    $html .= '<p class="pt24-home__section-intro">Wybierz miasto i znajdź sprawdzonych fachowców w swojej okolicy.</p>';
    $html .= '<div class="pt24-home__city-links">';
    foreach ( $cities as $cslug => $cname ) {
        $html .= '<a href="' . esc_url( home_url( "/{$cslug}/hydraulik/" ) ) . '"><span class="pt24-ico pt24-ico--pin" aria-hidden="true"></span>' . esc_html( $cname ) . '</a>';
    }
    $html .= '</div></section>';

    // For companies.
    $html .= '<section class="pt24-band pt24-home__forfirms"><h2>Jesteś fachowcem lub firmą usługową?</h2>'
        . '<p>Dołącz do PT24 i odbieraj zapytania od klientów z Twojej okolicy. Płacisz za dostęp do leadów, a nie prowizję od zleceń.</p>'
        . '<p><a class="pt24-btn pt24-btn--primary" href="' . esc_url( home_url( '/dla-firm/' ) ) . '">Pozyskuj klientów z PT24</a></p></section>';

    // FAQ.
    $faq = array(
        array( 'Czy korzystanie z PT24 jest płatne?', 'Nie. Dla osób zlecających serwis jest w 100% bezpłatny i niezobowiązujący.' ),
        array( 'Jak szybko otrzymam oferty?', 'Najczęściej w kilka godzin, a najpóźniej do 24 godzin od wysłania zgłoszenia.' ),
        array( 'Czy muszę przyjąć którąś z ofert?', 'Nie. Decyzję podejmujesz samodzielnie — żadne zgłoszenie nie jest zobowiązaniem.' ),
        array( 'W jakich miastach działacie?', 'Obsługujemy największe miasta w Polsce, a lista jest stale poszerzana o kolejne lokalizacje.' ),
        array( 'Jak dołączyć jako fachowiec?', 'Wejdź na stronę „Dla firm” i wypełnij zgłoszenie — pomożemy uruchomić Twój profil.' ),
    );
    $html .= '<section class="pt24-section pt24-home__faq"><h2>Najczęściej zadawane pytania</h2>';
    foreach ( $faq as $qa ) {
        $html .= '<details class="pt24-faq__item"><summary>' . esc_html( $qa[0] ) . '</summary><p>' . esc_html( $qa[1] ) . '</p></details>';
    }
    $html .= '</section>';

    // Final CTA.
    $html .= '<section class="pt24-cta-band"><h2>Potrzebujesz fachowca już dziś?</h2>'
        . '<p>Wybierz usługę i miasto, a my połączymy Cię ze sprawdzonymi specjalistami z Twojej okolicy.</p>'
        . '<p><a class="pt24-btn pt24-btn--primary" href="' . esc_url( home_url( '/jak-to-dziala/' ) ) . '">Rozpocznij teraz</a></p></section>';
    $html .= '</div>';

    return $html;
}

/**
 * Seed standard pages and return a map of slug => page ID.
 */
function pt24_seed_pages(): array {
    $ids = array();

    $ids['home'] = pt24_seed_page( 'strona-glowna', 'PT24.PRO — fachowcy w Twojej okolicy', pt24_seed_home_content() );

    $ids['how'] = pt24_seed_page(
        'jak-to-dziala',
        'Jak to działa?',
        '<div class="pt24-page">'
        . '<div class="pt24-band"><h2>Trzy proste kroki do realizacji zlecenia</h2>'
        . '<p>PT24.PRO łączy osoby szukające usług z lokalnymi, zweryfikowanymi fachowcami. Cały proces jest w 100% bezpłatny dla zlecających i zajmuje dosłownie chwilę.</p></div>'
        . '<p class="pt24-lead">Nie musisz już dzwonić po kilkunastu numerach ani czekać na oddzwonienie. Opisujesz zlecenie raz, a oferty przychodzą do Ciebie.</p>'
        . '<ol class="pt24-flow">'
        . '<li><strong>Opisz zlecenie</strong>Wybierz usługę i miasto, a następnie opisz zakres prac. Zajmuje to mniej niż minutę.</li>'
        . '<li><strong>Otrzymaj oferty</strong>Twoje zapytanie trafia do fachowców z Twojej okolicy. Pierwsze wyceny dostajesz zwykle w kilka godzin.</li>'
        . '<li><strong>Wybierz i zrealizuj</strong>Porównujesz ceny, opinie i terminy, po czym umawiasz się bezpośrednio z wybranym specjalistą.</li>'
        . '</ol>'
        . '<h2>Co zyskujesz?</h2>'
        . '<ul class="pt24-features">'
        . '<li><span class="pt24-feat-ico"><span class="pt24-ico pt24-ico--clock" aria-hidden="true"></span></span><strong>Oszczędność czasu</strong>Jedno zgłoszenie zamiast dziesiątek telefonów i maili.</li>'
        . '<li><span class="pt24-feat-ico"><span class="pt24-ico pt24-ico--tag" aria-hidden="true"></span></span><strong>Realne porównanie</strong>Konkretne oferty cenowe, a nie szacowanie po omacku.</li>'
        . '<li><span class="pt24-feat-ico"><span class="pt24-ico pt24-ico--shield" aria-hidden="true"></span></span><strong>Zaufani wykonawcy</strong>Opinie i oceny innych klientów u Twojego boku.</li>'
        . '<li><span class="pt24-feat-ico"><span class="pt24-ico pt24-ico--star" aria-hidden="true"></span></span><strong>Zero kosztów</strong>Korzystanie z serwisu dla zlecających jest bezpłatne.</li>'
        . '</ul>'
        . '<h2>Najczęstsze pytania</h2>'
        . '<h3>Czy muszę za coś zapłacić?</h3><p>Nie. Wysłanie zgłoszenia i otrzymanie ofert jest całkowicie darmowe i niezobowiązujące.</p>'
        . '<h3>Jak szybko dostanę odpowiedź?</h3><p>Większość zapytań otrzymuje pierwsze oferty w ciągu kilku godzin, najpóźniej do 24 godzin.</p>'
        . '<h3>Czy mogę odrzucić oferty?</h3><p>Tak. Decyzję o współpracy podejmujesz wyłącznie Ty — żadne zgłoszenie nie jest zobowiązaniem.</p>'
        . '<div class="pt24-cta-band"><h2>Gotowy, by zacząć?</h2>'
        . '<p>Wybierz usługę i miasto, a my połączymy Cię ze sprawdzonymi fachowcami.</p>'
        . '<p><a class="pt24-btn pt24-btn--primary" href="' . esc_url( home_url( '/' ) ) . '">Znajdź fachowca</a></p></div>'
        . '</div>'
    );

    $ids['pricing'] = pt24_seed_page(
        'dla-firm',
        'Dla firm — pozyskuj klientów z PT24',
        '<div class="pt24-page">'
        . '<div class="pt24-band"><h2>Otrzymuj zlecenia od klientów z Twojej okolicy</h2>'
        . '<p>Jesteś fachowcem lub prowadzisz firmę usługową? Dołącz do PT24.PRO i odbieraj zapytania od klientów, którzy szukają dokładnie tego, co oferujesz.</p></div>'
        . '<p class="pt24-lead">Płacisz za dostęp do leadów, a nie za prowizję od zleceń. Pełna kontrola nad tym, które zapytania odbierasz.</p>'
        . '<h2>Dlaczego warto dołączyć?</h2>'
        . '<ul class="pt24-features">'
        . '<li><span class="pt24-feat-ico"><span class="pt24-ico pt24-ico--pin" aria-hidden="true"></span></span><strong>Lokalni klienci</strong>Otrzymujesz zapytania z miast i usług, które realnie obsługujesz.</li>'
        . '<li><span class="pt24-feat-ico"><span class="pt24-ico pt24-ico--tag" aria-hidden="true"></span></span><strong>Bez prowizji</strong>Rozliczasz się ze zlecającym bezpośrednio — PT24 nie pobiera procentu od zleceń.</li>'
        . '<li><span class="pt24-feat-ico"><span class="pt24-ico pt24-ico--clock" aria-hidden="true"></span></span><strong>Szybkie leady</strong>Zapytania trafiają do Ciebie na bieżąco, zanim klient wybierze konkurencję.</li>'
        . '<li><span class="pt24-feat-ico"><span class="pt24-ico pt24-ico--star" aria-hidden="true"></span></span><strong>Reputacja i opinie</strong>Zbierasz oceny, które przyciągają kolejnych klientów.</li>'
        . '<li><span class="pt24-feat-ico"><span class="pt24-ico pt24-ico--shield" aria-hidden="true"></span></span><strong>Zweryfikowany profil</strong>Wyróżniasz się jako sprawdzony wykonawca w swojej okolicy.</li>'
        . '<li><span class="pt24-feat-ico"><span class="pt24-ico pt24-ico--lock" aria-hidden="true"></span></span><strong>Pełna kontrola</strong>Sam decydujesz, które zapytania odbierasz i kiedy zmieniasz pakiet.</li>'
        . '</ul>'
        . '<h2>Jak zacząć?</h2>'
        . '<ol class="pt24-flow">'
        . '<li><strong>Załóż profil</strong>Zgłoś firmę i wybierz kategorie usług oraz obszar działania.</li>'
        . '<li><strong>Odbieraj zapytania</strong>Powiadomimy Cię o nowych leadach z Twojej okolicy.</li>'
        . '<li><strong>Realizuj i rozwijaj</strong>Wyceniaj, realizuj zlecenia i zbieraj opinie klientów.</li>'
        . '</ol>'
        . '<h2>Pakiety i ceny</h2>'
        . '<table><thead><tr><th>Pakiet</th><th>Zakres</th><th>Cena</th></tr></thead><tbody>'
        . '<tr><td><strong>Start</strong></td><td>Profil firmy, do 10 zapytań / mies.</td><td>0 zł</td></tr>'
        . '<tr><td><strong>Profi</strong></td><td>Wyróżnienie, nielimitowane zapytania, statystyki</td><td>149 zł / mies.</td></tr>'
        . '<tr><td><strong>Premium</strong></td><td>Top pozycje, priorytetowe leady, opiekun konta</td><td>349 zł / mies.</td></tr>'
        . '</tbody></table>'
        . '<p class="pt24-note">Bez długich umów — pakiet możesz zmienić lub anulować w dowolnym momencie, bez okresu wypowiedzenia.</p>'
        . '<h2>Najczęstsze pytania</h2>'
        . '<details class="pt24-faq__item"><summary>Ile kosztuje dołączenie?</summary><p>Profil podstawowy jest bezpłatny. Płatne pakiety rozszerzają widoczność i limit zapytań.</p></details>'
        . '<details class="pt24-faq__item"><summary>Czy płacę prowizję od zleceń?</summary><p>Nie. Płacisz wyłącznie za pakiet — rozliczenie z klientem jest bezpośrednie.</p></details>'
        . '<details class="pt24-faq__item"><summary>Czy mogę zrezygnować w dowolnym momencie?</summary><p>Tak. Pakiet zmienisz lub anulujesz kiedy chcesz, bez okresu wypowiedzenia.</p></details>'
        . '<div class="pt24-cta-band"><h2>Zacznij pozyskiwać klientów</h2>'
        . '<p>Napisz do nas, a pomożemy Ci uruchomić profil i pierwsze kampanie.</p>'
        . '<p><a class="pt24-btn pt24-btn--primary" href="' . esc_url( home_url( '/kontakt/' ) ) . '">Skontaktuj się z nami</a></p></div>'
        . '</div>'
    );

    $ids['about'] = pt24_seed_page(
        'o-nas',
        'O nas',
        '<div class="pt24-page">'
        . '<div class="pt24-band"><h2>PT24.PRO — fachowcy od ręki</h2>'
        . '<p>Powstaliśmy, aby uprościć poszukiwanie rzetelnych specjalistów i połączyć klientów z lokalnymi wykonawcami z różnych branż.</p></div>'
        . '<p class="pt24-lead">Od mechaniki, przez hydraulikę i elektrykę, po remonty i odnawialne źródła energii — łączymy Cię z fachowcami, którzy realnie działają w Twojej okolicy.</p>'
        . '<p>Naszą misją jest oszczędzić Ci czasu i stresu. Zamiast przeszukiwać dziesiątki ogłoszeń, opisujesz zlecenie raz i otrzymujesz konkretne oferty od sprawdzonych wykonawców. Wierzymy, że dobry fachowiec powinien być w zasięgu kilku kliknięć.</p>'
        . '<h2>Nasze wartości</h2>'
        . '<ul class="pt24-features">'
        . '<li><span class="pt24-feat-ico"><span class="pt24-ico pt24-ico--shield" aria-hidden="true"></span></span><strong>Przejrzystość</strong>Jasne zasady i brak ukrytych kosztów dla zlecających.</li>'
        . '<li><span class="pt24-feat-ico"><span class="pt24-ico pt24-ico--star" aria-hidden="true"></span></span><strong>Jakość</strong>Weryfikujemy fachowców i zbieramy opinie klientów.</li>'
        . '<li><span class="pt24-feat-ico"><span class="pt24-ico pt24-ico--pin" aria-hidden="true"></span></span><strong>Lokalność</strong>Łączymy Cię ze specjalistami z Twojego miasta.</li>'
        . '<li><span class="pt24-feat-ico"><span class="pt24-ico pt24-ico--lock" aria-hidden="true"></span></span><strong>Bezpieczeństwo</strong>Dbamy o Twoje dane i kontakt zgodnie z RODO.</li>'
        . '</ul>'
        . '<h2>PT24 w liczbach</h2>'
        . '<table><thead><tr><th>Wskaźnik</th><th>Wartość</th></tr></thead><tbody>'
        . '<tr><td>Obsługiwane miasta</td><td>6 i kolejne w drodze</td></tr>'
        . '<tr><td>Kategorie usług</td><td>6 głównych branż</td></tr>'
        . '<tr><td>Czas na pierwszą ofertę</td><td>zwykle kilka godzin</td></tr>'
        . '</tbody></table>'
        . '<div class="pt24-cta-band"><h2>Dołącz do PT24</h2>'
        . '<p>Szukasz fachowca albo chcesz pozyskiwać klientów? Jesteś we właściwym miejscu.</p>'
        . '<p><a class="pt24-btn pt24-btn--primary" href="' . esc_url( home_url( '/jak-to-dziala/' ) ) . '">Zobacz, jak to działa</a></p></div>'
        . '</div>'
    );

    $ids['contact'] = pt24_seed_page(
        'kontakt',
        'Kontakt',
        '<div class="pt24-page">'
        . '<div class="pt24-band"><h2>Skontaktuj się z nami</h2>'
        . '<p>Masz pytanie dotyczące działania serwisu lub współpracy? Napisz do nas — odpowiadamy w dni robocze.</p></div>'
        . '<ul class="pt24-contact">'
        . '<li><strong>E-mail ogólny</strong><a href="mailto:kontakt@pt24.pro">kontakt@pt24.pro</a></li>'
        . '<li><strong>Dla firm</strong><a href="mailto:firmy@pt24.pro">firmy@pt24.pro</a></li>'
        . '<li><strong>Godziny pracy</strong>Pon.–pt. 9:00–17:00</li>'
        . '</ul>'
        . '<p class="pt24-note">Szukasz fachowca? Najszybszą drogą jest wypełnienie formularza na stronie wybranej usługi — Twoje zapytanie trafi bezpośrednio do specjalistów z Twojej okolicy.</p>'
        . '<h2>Często wybierane usługi</h2>'
        . '<p>Przejdź od razu do popularnych kategorii i zamów bezpłatną wycenę:</p>'
        . '<ul class="pt24-links">'
        . '<li><a href="' . esc_url( home_url( '/warszawa/hydraulik/' ) ) . '">Hydraulik Warszawa</a></li>'
        . '<li><a href="' . esc_url( home_url( '/krakow/elektryk/' ) ) . '">Elektryk Kraków</a></li>'
        . '<li><a href="' . esc_url( home_url( '/wroclaw/mechanik/' ) ) . '">Mechanik Wrocław</a></li>'
        . '<li><a href="' . esc_url( home_url( '/gdansk/fotowoltaika/' ) ) . '">Fotowoltaika Gdańsk</a></li>'
        . '</ul>'
        . '</div>'
    );

    $ids['privacy'] = pt24_seed_page(
        'polityka-prywatnosci',
        'Polityka prywatności',
        '<div class="pt24-page">'
        . '<p class="pt24-lead">Niniejsza polityka opisuje, w jaki sposób PT24.PRO przetwarza dane osobowe użytkowników serwisu zgodnie z RODO.</p>'
        . '<h2>Administrator danych</h2><p>Administratorem danych jest operator serwisu PT24.PRO. Kontakt: <a href="mailto:kontakt@pt24.pro">kontakt@pt24.pro</a>.</p>'
        . '<h2>Zakres i cel przetwarzania</h2><p>Przetwarzamy dane podane w formularzach (imię, telefon, e-mail, treść zapytania) wyłącznie w celu skojarzenia zapytania z odpowiednimi fachowcami i obsługi zgłoszenia.</p>'
        . '<h2>Podstawa prawna</h2><p>Dane przetwarzamy na podstawie zgody (art. 6 ust. 1 lit. a RODO) oraz w celu realizacji usługi (art. 6 ust. 1 lit. b RODO).</p>'
        . '<h2>Twoje prawa</h2><p>Masz prawo dostępu do danych, ich sprostowania, usunięcia, ograniczenia przetwarzania oraz wniesienia sprzeciwu i skargi do organu nadzorczego.</p>'
        . '<h2>Pliki cookies</h2><p>Serwis wykorzystuje pliki cookies w celach statystycznych i poprawy działania strony. Możesz zarządzać nimi w ustawieniach przeglądarki.</p>'
        . '<p class="pt24-note">W sprawach dotyczących danych osobowych napisz na adres <a href="mailto:kontakt@pt24.pro">kontakt@pt24.pro</a> — odpowiemy bez zbędnej zwłoki.</p>'
        . '</div>'
    );

    $ids['terms'] = pt24_seed_page(
        'regulamin',
        'Regulamin',
        '<div class="pt24-page">'
        . '<p class="pt24-lead">Regulamin określa zasady korzystania z serwisu PT24.PRO przez osoby poszukujące usług oraz fachowców.</p>'
        . '<h2>§1 Postanowienia ogólne</h2><p>Serwis PT24.PRO umożliwia kojarzenie użytkowników poszukujących usług z fachowcami gotowymi je zrealizować.</p>'
        . '<h2>§2 Zasady korzystania</h2><p>Korzystanie z serwisu przez zlecających jest bezpłatne. Wysłanie zapytania nie jest zobowiązaniem do zawarcia umowy.</p>'
        . '<h2>§3 Odpowiedzialność</h2><p>PT24.PRO pełni rolę pośrednika informacyjnego. Umowa o wykonanie usługi zawierana jest bezpośrednio między klientem a fachowcem.</p>'
        . '<h2>§4 Reklamacje</h2><p>Reklamacje dotyczące działania serwisu można zgłaszać na adres <a href="mailto:kontakt@pt24.pro">kontakt@pt24.pro</a>.</p>'
        . '<h2>§5 Postanowienia końcowe</h2><p>Operator zastrzega sobie prawo do zmiany regulaminu. Aktualna wersja jest zawsze dostępna na tej stronie.</p>'
        . '</div>'
    );

    // "Dodaj firmę" sign-up page — form injected dynamically via the_content filter.
    $ids['dodaj-firme'] = pt24_seed_page(
        'dodaj-firme',
        'Dodaj firmę',
        '<!-- pt24-add-firm-form -->'
    );

    return $ids;
}

/**
 * Generate service/city landing pages via the landing CPT engine.
 */
function pt24_seed_landings(): void {
    if ( ! class_exists( 'PearBlog_PT24_Landing_CPT' ) ) {
        return;
    }
    PearBlog_PT24_Landing_CPT::bulk_generate();
}

/**
 * Build the primary and footer navigation menus and assign them to locations.
 *
 * @param array $ids slug => page ID map.
 */
function pt24_seed_menus( array $ids ): void {
    $primary_name = 'PT24 Menu główne';
    $menu         = wp_get_nav_menu_object( $primary_name );
    if ( ! $menu ) {
        $menu_id = wp_create_nav_menu( $primary_name );
        if ( ! is_wp_error( $menu_id ) ) {
            $items = array(
                array( 'title' => 'Start', 'object_id' => $ids['home'] ?? 0 ),
                array( 'title' => 'Jak to działa', 'object_id' => $ids['how'] ?? 0 ),
                array( 'title' => 'Dla firm', 'object_id' => $ids['pricing'] ?? 0 ),
                array( 'title' => 'O nas', 'object_id' => $ids['about'] ?? 0 ),
                array( 'title' => 'Kontakt', 'object_id' => $ids['contact'] ?? 0 ),
            );
            foreach ( $items as $item ) {
                if ( empty( $item['object_id'] ) ) {
                    continue;
                }
                wp_update_nav_menu_item(
                    $menu_id,
                    0,
                    array(
                        'menu-item-title'     => $item['title'],
                        'menu-item-object'    => 'page',
                        'menu-item-object-id' => $item['object_id'],
                        'menu-item-type'      => 'post_type',
                        'menu-item-status'    => 'publish',
                    )
                );
            }
            $locations            = get_theme_mod( 'nav_menu_locations', array() );
            $locations['primary'] = $menu_id;
            set_theme_mod( 'nav_menu_locations', $locations );
        }
    }

    $footer_name = 'PT24 Stopka';
    $fmenu       = wp_get_nav_menu_object( $footer_name );
    if ( ! $fmenu ) {
        $fmenu_id = wp_create_nav_menu( $footer_name );
        if ( ! is_wp_error( $fmenu_id ) ) {
            foreach ( array( 'privacy' => 'Polityka prywatności', 'terms' => 'Regulamin', 'contact' => 'Kontakt' ) as $key => $title ) {
                if ( empty( $ids[ $key ] ) ) {
                    continue;
                }
                wp_update_nav_menu_item(
                    $fmenu_id,
                    0,
                    array(
                        'menu-item-title'     => $title,
                        'menu-item-object'    => 'page',
                        'menu-item-object-id' => $ids[ $key ],
                        'menu-item-type'      => 'post_type',
                        'menu-item-status'    => 'publish',
                    )
                );
            }
            $locations           = get_theme_mod( 'nav_menu_locations', array() );
            $locations['footer'] = $fmenu_id;
            set_theme_mod( 'nav_menu_locations', $locations );
        }
    }
}

/**
 * Add a menu item to a named menu only if it isn't already present (idempotent).
 */
function pt24_add_menu_item_once( string $menu_name, string $title, int $page_id ): void {
    if ( $page_id <= 0 ) {
        return;
    }
    $menu = wp_get_nav_menu_object( $menu_name );
    if ( ! $menu ) {
        return;
    }
    $items = wp_get_nav_menu_items( $menu->term_id );
    if ( is_array( $items ) ) {
        foreach ( $items as $item ) {
            if ( (int) $item->object_id === $page_id || strtolower( trim( (string) $item->title ) ) === strtolower( $title ) ) {
                return;
            }
        }
    }
    wp_update_nav_menu_item(
        $menu->term_id,
        0,
        array(
            'menu-item-title'     => $title,
            'menu-item-object'    => 'page',
            'menu-item-object-id' => $page_id,
            'menu-item-type'      => 'post_type',
            'menu-item-status'    => 'publish',
        )
    );
}

/**
 * Insert or refresh a single seeded blog post.
 */
function pt24_seed_post( string $slug, string $title, string $content, int $cat_id ): void {
    $existing = get_posts( array(
        'name'             => $slug,
        'post_type'        => 'post',
        'post_status'      => 'any',
        'numberposts'      => 1,
        'suppress_filters' => true,
    ) );

    if ( ! empty( $existing ) ) {
        $post_id = (int) $existing[0]->ID;
        wp_update_post( array(
            'ID'           => $post_id,
            'post_title'   => $title,
            'post_content' => $content,
            'post_status'  => 'publish',
        ) );
    } else {
        $post_id = (int) wp_insert_post( array(
            'post_title'   => $title,
            'post_name'    => $slug,
            'post_content' => $content,
            'post_status'  => 'publish',
            'post_type'    => 'post',
        ) );
    }

    if ( $post_id > 0 ) {
        update_post_meta( $post_id, '_pt24_seeded', PT24_SEED_VERSION );
        // Store slug so the blog archive template can resolve the branded thumbnail.
        update_post_meta( $post_id, '_pt24_thumb_slug', $slug );
        if ( $cat_id > 0 ) {
            wp_set_post_categories( $post_id, array( $cat_id ) );
        }
    }
}

/**
 * Seed the PT24 blog: posts page, categories and a set of SEO articles.
 */
function pt24_seed_blog(): void {
    // 1) Blog page used as the posts archive (/blog/).
    $blog_page = get_page_by_path( 'blog' );
    if ( $blog_page instanceof WP_Post ) {
        $blog_id = (int) $blog_page->ID;
    } else {
        $blog_id = (int) wp_insert_post( array(
            'post_title'   => 'Blog',
            'post_name'    => 'blog',
            'post_content' => '',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ) );
    }
    if ( $blog_id > 0 ) {
        update_option( 'page_for_posts', $blog_id );
        update_post_meta( $blog_id, '_pt24_seeded', PT24_SEED_VERSION );
    }

    // 2) Categories.
    $cat_defs = array(
        'porady'    => 'Porady',
        'koszty'    => 'Koszty i cennik',
        'poradniki' => 'Poradniki',
    );
    $cats = array();
    foreach ( $cat_defs as $cslug => $cname ) {
        $term = term_exists( $cslug, 'category' );
        if ( ! $term ) {
            $term = wp_insert_term( $cname, 'category', array( 'slug' => $cslug ) );
        }
        if ( ! is_wp_error( $term ) ) {
            $cats[ $cslug ] = (int) ( is_array( $term ) ? $term['term_id'] : $term );
        }
    }

    $cta = function ( string $path, string $label ): string {
        return '<p><strong>' . esc_html( $label ) . '</strong> <a href="' . esc_url( home_url( $path ) ) . '">Zamów bezpłatną wycenę przez PT24</a>.</p>';
    };

    // 3) Articles.
    $posts = array(
        array(
            'slug' => 'jak-wybrac-dobrego-hydraulika',
            'cat'  => 'porady',
            'title'=> 'Jak wybrać dobrego hydraulika? Praktyczny poradnik',
            'body' => '<p>Awaria instalacji wodnej potrafi sparaliżować dom w kilka minut. Zanim jednak zadzwonisz po pierwszego z brzegu fachowca, warto wiedzieć, na co zwrócić uwagę, by uniknąć przepłacania i niesolidnego wykonania.</p>'
                . '<h2>Na co zwrócić uwagę przy wyborze?</h2>'
                . '<ul><li>Opinie i oceny poprzednich klientów.</li><li>Doświadczenie w konkretnym typie prac (awarie, montaż, instalacje).</li><li>Przejrzysta, pisemna wycena przed rozpoczęciem prac.</li><li>Gotowość do wystawienia faktury lub rachunku.</li></ul>'
                . '<h2>Ile kosztuje hydraulik?</h2>'
                . '<p>Stawki zależą od zakresu: drobna naprawa to zwykle 120–300 zł, usunięcie awarii 150–500 zł, a montaż armatury 200–450 zł. Dokładną cenę poznasz po opisaniu zlecenia i oględzinach.</p>'
                . $cta( '/warszawa/hydraulik/', 'Potrzebujesz hydraulika?' ),
        ),
        array(
            'slug' => 'ile-kosztuje-remont-lazienki-2026',
            'cat'  => 'koszty',
            'title'=> 'Ile kosztuje remont łazienki w 2026 roku?',
            'body' => '<p>Remont łazienki to jedna z najczęściej planowanych inwestycji w domu. Koszt zależy od metrażu, jakości materiałów i zakresu prac — od drobnego odświeżenia po kompleksową przebudowę „pod klucz”.</p>'
                . '<h2>Orientacyjne koszty</h2>'
                . '<ul><li>Remont „pod klucz” (do 6 m²): 12 000–30 000 zł.</li><li>Układanie płytek (robocizna): 80–160 zł/m².</li><li>Biały montaż: 600–1 500 zł.</li><li>Skucie starych płytek: 40–90 zł/m².</li></ul>'
                . '<h2>Jak nie przepłacić?</h2>'
                . '<p>Zbierz kilka wycen i porównaj zakres prac, a nie tylko cenę. Rzetelna ekipa przedstawi harmonogram i kosztorys przed startem.</p>'
                . $cta( '/warszawa/remont-lazienki/', 'Planujesz remont łazienki?' ),
        ),
        array(
            'slug' => 'fotowoltaika-2026-czy-sie-oplaca',
            'cat'  => 'poradniki',
            'title'=> 'Fotowoltaika 2026 — czy to się jeszcze opłaca?',
            'body' => '<p>Mimo zmian w systemie rozliczeń własna instalacja PV wciąż obniża rachunki za prąd i zwiększa niezależność energetyczną. Kluczem jest dobranie mocy do realnego zużycia.</p>'
                . '<h2>Co wpływa na opłacalność?</h2>'
                . '<ul><li>Roczne zużycie energii i profil jej poboru.</li><li>Dobór mocy instalacji oraz ewentualny magazyn energii.</li><li>Jakość paneli, falownika i samego montażu.</li></ul>'
                . '<h2>Czas zwrotu</h2>'
                . '<p>Najczęściej 6–9 lat, zależnie od zużycia, cen energii i magazynu. Dobry instalator wyliczy moc na podstawie Twoich rachunków i zajmie się formalnościami.</p>'
                . $cta( '/gdansk/fotowoltaika/', 'Myślisz o fotowoltaice?' ),
        ),
        array(
            'slug' => 'elektryk-uprawnienia-sep',
            'cat'  => 'porady',
            'title'=> 'Elektryk z uprawnieniami SEP — dlaczego to takie ważne',
            'body' => '<p>Prace przy instalacji elektrycznej to nie miejsce na oszczędności i improwizację. Uprawnienia SEP potwierdzają, że fachowiec zna przepisy i wykona pracę bezpiecznie.</p>'
                . '<h2>Co dają uprawnienia SEP?</h2>'
                . '<ul><li>Legalny i bezpieczny montaż oraz pomiary.</li><li>Protokół pomiarów wymagany przy odbiorach i ubezpieczeniach.</li><li>Mniejsze ryzyko usterek i pożaru instalacji.</li></ul>'
                . '<h2>Zapytaj o certyfikat</h2>'
                . '<p>Przed rozpoczęciem prac możesz poprosić o okazanie aktualnych uprawnień. Rzetelny elektryk nie będzie miał z tym problemu.</p>'
                . $cta( '/krakow/elektryk/', 'Szukasz elektryka?' ),
        ),
        array(
            'slug' => 'pompa-ciepla-czy-kociol-gazowy',
            'cat'  => 'koszty',
            'title'=> 'Pompa ciepła czy kocioł gazowy? Porównanie kosztów',
            'body' => '<p>Wybór źródła ciepła to decyzja na lata. Pompa ciepła kusi niższymi kosztami eksploatacji i dotacjami, kocioł gazowy — niższą ceną zakupu. Co wybrać?</p>'
                . '<h2>Koszt i eksploatacja</h2>'
                . '<ul><li>Pompa ciepła (z montażem): 25 000–55 000 zł, niższe rachunki.</li><li>Kocioł gazowy: tańszy zakup, ale rosnące koszty paliwa.</li><li>Dotacje (np. „Czyste Powietrze”) poprawiają opłacalność pompy.</li></ul>'
                . '<h2>Co wybrać?</h2>'
                . '<p>Dla dobrze ocieplonych budynków pompa ciepła zwykle wygrywa w perspektywie kilku lat. Instalator wykona uproszczony audyt i dobierze moc.</p>'
                . $cta( '/poznan/pompa-ciepla/', 'Rozważasz pompę ciepła?' ),
        ),        array(
            'slug' => 'klimatyzacja-montaz-serwis-koszty',
            'cat'  => 'koszty',
            'title'=> 'Klimatyzacja w domu — montaż, serwis i koszty 2026',
            'body' => '<p>Klimatyzacja stała się standardem nie tylko w biurach, ale też w mieszkaniach i domach. Warto wiedzieć, ile kosztuje montaż i na co zwrócić uwagę przy wyborze urządzenia.</p>'
                . '<h2>Ile kosztuje klimatyzacja?</h2>'
                . '<ul><li>Klimatyzator split (montaż + urządzenie): 3 000–8 000 zł/pomieszczenie.</li><li>Montaż multi-split: 8 000–20 000 zł (kilka pomieszczeń).</li><li>Serwis/czyszczenie: 200–400 zł/rok.</li></ul>'
                . '<h2>Co obejmuje montaż klimatyzacji?</h2>'
                . '<p>Dobry instalator dobierze moc do kubatury, wykona okablowanie, podłączy czynnik chłodniczy (F-GAZ) i uruchomi urządzenie. Certyfikat F-GAZ jest obowiązkowy przy pracach z czynnikiem.</p>'
                . '<h2>Serwis i czyszczenie</h2>'
                . '<p>Klimatyzacja wymaga czyszczenia filtrów co miesiąc i przeglądu serwisowego co rok. Zaniedbany serwis to wyższe zużycie prądu i ryzyko awarii.</p>'
                . $cta( '/katowice/klimatyzacja/', 'Chcesz zainstalować klimatyzację?' ),
        ),
        array(
            'slug' => 'laweta-pomoc-drogowa-co-warto-wiedziec',
            'cat'  => 'porady',
            'title'=> 'Laweta i pomoc drogowa — co warto wiedzieć przed awarią',
            'body' => '<p>Awaria samochodu zawsze zaskakuje w najmniej oczekiwanym momencie. Dobrze jest wiedzieć, jak szybko zorganizować pomoc, zanim trzeba będzie z niej skorzystać.</p>'
                . '<h2>Kiedy wezwać lawetę?</h2>'
                . '<ul><li>Samochód nie odpala i nie można ustalić przyczyny w terenie.</li><li>Wypadek lub uszkodzenie pojazdu uniemożliwiające bezpieczną jazdę.</li><li>Awaria na autostradzie lub ekspresówce (holowanie to ostateczność).</li></ul>'
                . '<h2>Ile kosztuje laweta?</h2>'
                . '<p>Stawki zaczynają się od 100–200 zł za dojazd i transport w obrębie miasta. Długodystansowe holowanie to 5–12 zł/km. Zawsze pytaj o cenę całościową przed zamówieniem.</p>'
                . '<h2>Jak szybko przyjedzie pomoc?</h2>'
                . '<p>Sprawdzeni partnerzy PT24 działają 24/7. Czas przyjazdu w mieście to zazwyczaj 30–60 minut. Na autostradzie może być nieco dłużej.</p>'
                . $cta( '/warszawa/laweta/', 'Potrzebujesz lawety?' ),
        ),
        array(
            'slug' => 'wulkanizacja-wymiana-opon-poradnik',
            'cat'  => 'poradniki',
            'title'=> 'Wulkanizacja i wymiana opon — kiedy i jak?',
            'body' => '<p>Sezonowa wymiana opon to obowiązek każdego kierowcy. Dobra wulkanizacja skróci ten czas do minimum i zadbaje o prawidłowe wyważenie.</p>'
                . '<h2>Kiedy wymieniać opony?</h2>'
                . '<ul><li>Zimowe montuj przed 1 listopada, letnie — po połowie marca.</li><li>Bieżnik zimowy: min. 4 mm, letni: min. 1,6 mm (dla bezpieczeństwa zalecane 3 mm).</li><li>Wiek opony powyżej 6–8 lat to też powód wymiany.</li></ul>'
                . '<h2>Co obejmuje wizyta u wulkanizatora?</h2>'
                . '<p>Standardowo: wymiana opon, wyważenie kół i kontrola ciśnienia. Dopłata za mycie felg czy sprawdzenie hamulców bywa przydatna.</p>'
                . '<h2>Ile kosztuje wymiana opon?</h2>'
                . '<p>Wymiana 4 kół z wyważeniem: 100–200 zł (osobówka). Przechowywanie sezonowe: 150–250 zł/sezon.</p>'
                . $cta( '/wroclaw/wulkanizacja/', 'Szukasz wulkanizacji?' ),
        ),
        array(
            'slug' => 'instalacje-gazowe-bezpieczenstwo-przeglad',
            'cat'  => 'porady',
            'title'=> 'Instalacje gazowe — bezpieczeństwo, przegląd i certyfikaty',
            'body' => '<p>Instalacja gazowa w domu wymaga szczególnej dbałości. Błędy przy wykonaniu lub zaniedbanie przeglądów mogą prowadzić do poważnych awarii.</p>'
                . '<h2>Kto może wykonywać prace przy gazie?</h2>'
                . '<p>Wyłącznie osoby z certyfikatem gazowym. W Polsce obowiązują uprawnienia SEP G3 (instalacje gazowe) — zawsze sprawdzaj, czy fachowiec posiada aktualne dokumenty.</p>'
                . '<h2>Jak często przegląd instalacji gazowej?</h2>'
                . '<ul><li>Kocioł gazowy: przegląd co roku (obowiązek wynikający z prawa budowlanego).</li><li>Instalacja wewnętrzna: co 5 lat lub po remoncie.</li><li>Piec gazowy starszy niż 15 lat: rozważ wymianę na nowocześniejszy model.</li></ul>'
                . '<h2>Objawy problemów z instalacją</h2>'
                . '<p>Zapach gazu, nieregularne płomienie, częste zagasanie palnika — to sygnały do natychmiastowego kontaktu z gazownikiem. Nie lekceważ tych objawów.</p>'
                . $cta( '/bydgoszcz/instalacje-gazowe/', 'Potrzebujesz gazownika?' ),
        ),    );

    foreach ( $posts as $p ) {
        pt24_seed_post( $p['slug'], $p['title'], $p['body'], $cats[ $p['cat'] ] ?? 0 );
    }

    // 3b) Back-fill _pt24_thumb_slug on existing posts (runs even when post was seeded before).
    $known_slugs = array_column( $posts, 'slug' );
    foreach ( $known_slugs as $pslug ) {
        $found = get_posts( array( 'name' => $pslug, 'post_type' => 'post', 'numberposts' => 1, 'suppress_filters' => true ) );
        if ( ! empty( $found ) ) {
            update_post_meta( (int) $found[0]->ID, '_pt24_thumb_slug', $pslug );
        }
    }

    // 4) Navigation links to the blog (idempotent on the existing primary menu).
    pt24_add_menu_item_once( 'PT24 Menu główne', 'Blog', $blog_id );

    // 5) Tidy up the default WordPress sample post.
    $hello = get_posts( array( 'name' => 'hello-world', 'post_type' => 'post', 'post_status' => 'any', 'numberposts' => 1, 'suppress_filters' => true ) );
    if ( ! empty( $hello ) && false !== stripos( (string) $hello[0]->post_title, 'hello' ) ) {
        wp_trash_post( (int) $hello[0]->ID );
    }
}

/**
 * Seed company (firm) profiles — a few multi-trade firms per city.
 */
function pt24_seed_firms(): void {
    $cities = array(
        'warszawa' => 'Warszawa',
        'krakow'   => 'Kraków',
        'wroclaw'  => 'Wrocław',
        'poznan'   => 'Poznań',
        'gdansk'   => 'Gdańsk',
        'katowice' => 'Katowice',
    );
    $all_services = 'hydraulik,elektryk,mechanik,pompa-ciepla,remont-lazienki,fotowoltaika';
    $brands = array(
        array( 'pre' => 'FachowcyPro', 'desc' => 'Wielobranżowa ekipa z wieloletnim doświadczeniem.' ),
        array( 'pre' => 'Serwis24',    'desc' => 'Lokalny serwis dostępny od ręki, także w nagłych awariach.' ),
        array( 'pre' => 'MasterFix',   'desc' => 'Specjaliści od remontów, instalacji i nowoczesnych systemów grzewczych.' ),
    );

    foreach ( $cities as $cslug => $cname ) {
        foreach ( $brands as $brand ) {
            $name    = $brand['pre'] . ' ' . $cname;
            $slug    = sanitize_title( $brand['pre'] . '-' . $cslug );
            $seed    = crc32( $cslug . $brand['pre'] );
            $rating  = number_format( 4.6 + ( $seed % 4 ) / 10, 1, ',', '' );
            $jobs    = 80 + ( $seed % 320 );
            $year    = 2008 + ( $seed % 14 );
            $content = '<p>' . $brand['desc'] . ' Działamy na terenie miasta ' . $cname . ' i okolic, oferując kompleksowe usługi: hydraulika, elektryka, mechanika, montaż pomp ciepła, remonty łazienek oraz fotowoltaikę.</p>'
                . '<p>Stawiamy na terminowość, przejrzyste wyceny i jakość wykonania. Każde zlecenie traktujemy indywidualnie, a po realizacji zbieramy opinie klientów.</p>';

            $existing = get_posts( array( 'name' => $slug, 'post_type' => 'pt24_firm', 'post_status' => 'any', 'numberposts' => 1, 'suppress_filters' => true ) );
            if ( ! empty( $existing ) ) {
                $fid = (int) $existing[0]->ID;
                wp_update_post( array( 'ID' => $fid, 'post_title' => $name, 'post_content' => $content, 'post_status' => 'publish' ) );
            } else {
                $fid = (int) wp_insert_post( array( 'post_title' => $name, 'post_name' => $slug, 'post_content' => $content, 'post_status' => 'publish', 'post_type' => 'pt24_firm' ) );
            }

            if ( $fid > 0 ) {
                update_post_meta( $fid, '_pt24_seeded', PT24_SEED_VERSION );
                update_post_meta( $fid, 'pt24_firm_city', $cslug );
                update_post_meta( $fid, 'pt24_firm_city_name', $cname );
                update_post_meta( $fid, 'pt24_firm_services', $all_services );
                update_post_meta( $fid, 'pt24_firm_rating', $rating );
                update_post_meta( $fid, 'pt24_firm_jobs', (string) $jobs );
                update_post_meta( $fid, 'pt24_firm_established', (string) $year );
            }
        }
    }
}

/**
 * Main seed runner — guarded so it runs once per version on the pt24 host.
 */
function pt24_run_content_seed(): void {
    if ( ! pt24_seed_is_pt24() ) {
        return;
    }

    if ( get_option( 'pt24_content_seed_version' ) === PT24_SEED_VERSION ) {
        return;
    }

    $ids = pt24_seed_pages();

    if ( ! empty( $ids['home'] ) ) {
        update_option( 'show_on_front', 'page' );
        update_option( 'page_on_front', $ids['home'] );
    }

    pt24_seed_landings();
    pt24_seed_menus( $ids );
    pt24_seed_blog();
    pt24_seed_firms();

    flush_rewrite_rules();

    update_option( 'pt24_content_seed_version', PT24_SEED_VERSION );
}
add_action( 'init', 'pt24_run_content_seed', 130 );

/**
 * Robust one-time rewrite flush.
 *
 * The landing CPT registers its /{city}/{service} rewrite rules during `init`,
 * which can fire after the seeder's own flush. Running a guarded flush on
 * `wp_loaded` (after every init callback has registered its rules) guarantees
 * the rules are persisted to the database exactly once.
 */
function pt24_seed_flush_rewrites(): void {
    if ( ! pt24_seed_is_pt24() ) {
        return;
    }
    if ( get_option( 'pt24_rewrite_flushed_version' ) === PT24_SEED_VERSION ) {
        return;
    }
    flush_rewrite_rules();
    update_option( 'pt24_rewrite_flushed_version', PT24_SEED_VERSION );
}
add_action( 'wp_loaded', 'pt24_seed_flush_rewrites', 99 );
