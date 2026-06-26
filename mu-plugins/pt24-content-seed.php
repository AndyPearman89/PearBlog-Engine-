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

const PT24_SEED_VERSION = '3.0.0';

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
 * Build the homepage HTML — PRO version with finder widget and full Scale_Data.
 */
function pt24_seed_home_content(): string {
    // Use Scale_Data for full lists if available, otherwise fallback.
    if ( class_exists( 'PT24_Scale_Data' ) ) {
        $all_services = PT24_Scale_Data::services();
        $all_cities   = PT24_Scale_Data::cities();
    } else {
        $all_services = array(
            'mechanik'        => array( 'name' => 'Mechanik',        'icon' => 'wrench' ),
            'elektryk'        => array( 'name' => 'Elektryk',        'icon' => 'zap' ),
            'hydraulik'       => array( 'name' => 'Hydraulik',       'icon' => 'droplet' ),
            'budowlaniec'     => array( 'name' => 'Budowlaniec',     'icon' => 'home' ),
            'malowanie'       => array( 'name' => 'Malowanie',       'icon' => 'home' ),
            'montaz'          => array( 'name' => 'Montaż',          'icon' => 'wrench' ),
            'ogrod'           => array( 'name' => 'Ogród',           'icon' => 'grid' ),
            'pompa-ciepla'    => array( 'name' => 'Pompa ciepła',    'icon' => 'thermometer' ),
        );
        $all_cities = array(
            'warszawa' => array( 'name' => 'Warszawa' ), 'krakow'   => array( 'name' => 'Kraków' ),
            'wroclaw'  => array( 'name' => 'Wrocław' ),  'poznan'   => array( 'name' => 'Poznań' ),
            'gdansk'   => array( 'name' => 'Gdańsk' ),   'katowice' => array( 'name' => 'Katowice' ),
        );
    }

    $n_cities   = count( $all_cities );
    $n_services = count( $all_services );

    // Build service options for finder select.
    $service_opts = '';
    foreach ( $all_services as $s_slug => $s_data ) {
        $service_opts .= '<option value="' . esc_attr( $s_slug ) . '">' . esc_html( $s_data['name'] ) . '</option>';
    }
    // Build city datalist options.
    $city_datalist = '';
    foreach ( $all_cities as $c_slug => $c_data ) {
        $city_datalist .= '<option value="' . esc_attr( $c_data['name'] ) . '" data-slug="' . esc_attr( $c_slug ) . '">';
    }

    $h          = esc_url( home_url( '/' ) );
    $szukaj_url = esc_url( home_url( '/szukaj/' ) );
    $dodaj_url  = esc_url( home_url( '/dodaj-zapytanie/' ) );

    $html = '<div class="pt24-home pt24-page">';

    // === HERO SECTION ===
    $html .= '<section class="pt24-home__hero">';
    $html .= '<div class="pt24-home__hero-content">';
    $html .= '<h1>Znajdź sprawdzonego <span class="pt24-home__hero-accent">fachowca</span> w kilka minut</h1>';
    $html .= '<p class="pt24-home__hero-sub">Wyślij jedno zapytanie. Otrzymaj wiele ofert od zweryfikowanych specjalistów w Twojej okolicy.</p>';

    // Trust badges
    $html .= '<ul class="pt24-home__trust-badges">';
    $html .= '<li><span class="pt24-ico pt24-ico--clock" aria-hidden="true"></span><strong>Szybko i wygodnie</strong><span>Oszczędzasz czas</span></li>';
    $html .= '<li><span class="pt24-ico pt24-ico--shield" aria-hidden="true"></span><strong>Bez zobowiązań</strong><span>Za darmo dla klientów</span></li>';
    $html .= '<li><span class="pt24-ico pt24-ico--star" aria-hidden="true"></span><strong>Sprawdzone firmy</strong><span>Zweryfikowane profile</span></li>';
    $html .= '</ul>';

    // Dual CTA buttons
    $html .= '<div class="pt24-home__hero-ctas">';
    $html .= '<a class="pt24-btn pt24-btn--primary pt24-btn--lg" href="' . $dodaj_url . '"><span class="pt24-ico pt24-ico--tag" aria-hidden="true"></span> DODAJ ZAPYTANIE<small>i otrzymaj oferty</small></a>';
    $html .= '<a class="pt24-btn pt24-btn--ghost pt24-btn--lg" href="' . $szukaj_url . '"><span class="pt24-ico pt24-ico--pin" aria-hidden="true"></span> ZNAJDŹ FACHOWCA<small>Przeglądaj firmy</small></a>';
    $html .= '</div>';

    $html .= '<p class="pt24-home__hero-note"><span class="pt24-ico pt24-ico--shield" aria-hidden="true"></span> Dla klientów – całkowicie <strong>ZA DARMO!</strong></p>';
    $html .= '</div>';
    $html .= '</section>';

    // === POPULAR CATEGORIES ===
    $categories = array(
        array( 'mechanik',    'Mechanik',     'Samochody',        'wrench' ),
        array( 'elektryk',    'Elektryk',     'Instalacje',       'zap' ),
        array( 'hydraulik',   'Hydraulik',    'Wod.-kan.',        'droplet' ),
        array( 'budowlaniec', 'Budowlaniec',  'Remonty',          'home' ),
        array( 'malowanie',   'Malowanie',    'Wnętrza',          'home' ),
        array( 'montaz',      'Montaż',       'i naprawy',        'wrench' ),
        array( 'ogrod',       'Ogród',        'i pielęgnacja',    'grid' ),
    );
    $html .= '<section class="pt24-home__categories" id="uslugi">';
    $html .= '<h2>Popularne kategorie</h2>';
    $html .= '<div class="pt24-home__cat-grid">';
    foreach ( $categories as $cat ) {
        $html .= '<a href="' . esc_url( home_url( '/szukaj/?usluga=' . $cat[0] ) ) . '" class="pt24-home__cat-item">'
            . '<span class="pt24-home__cat-icon"><span class="pt24-ico pt24-ico--' . esc_attr( $cat[3] ) . '" aria-hidden="true"></span></span>'
            . '<strong>' . esc_html( $cat[1] ) . '</strong>'
            . '<span class="pt24-home__cat-sub">' . esc_html( $cat[2] ) . '</span>'
            . '</a>';
    }
    $html .= '<a href="' . $szukaj_url . '" class="pt24-home__cat-item pt24-home__cat-item--more">'
        . '<span class="pt24-home__cat-icon"><span class="pt24-ico pt24-ico--grid" aria-hidden="true"></span></span>'
        . '<strong>I wiele więcej</strong>'
        . '<span class="pt24-home__cat-sub">Zobacz wszystkie</span>'
        . '</a>';
    $html .= '</div>';
    $html .= '</section>';

    // === HOW IT WORKS ===
    $html .= '<section class="pt24-home__how">';
    $html .= '<h2>Jak to działa?</h2>';
    $html .= '<ol class="pt24-flow">';
    $html .= '<li><span class="pt24-flow__num">1</span><strong>Dodaj zapytanie</strong><p>Opisz czego potrzebujesz w prostym formularzu.</p></li>';
    $html .= '<li><span class="pt24-flow__num">2</span><strong>Otrzymaj oferty</strong><p>Firmy same zgłoszą się z najlepszymi ofertami.</p></li>';
    $html .= '<li><span class="pt24-flow__num">3</span><strong>Wybierz najlepszą</strong><p>Porównaj ceny, opinie i wybierz wykonawcę.</p></li>';
    $html .= '</ol>';
    $html .= '</section>';

    // === STATS BAR ===
    $html .= '<section class="pt24-home__stats-bar">';
    $html .= '<div class="pt24-home__stats-grid">';
    $html .= '<div class="pt24-home__stat"><strong>10 000+</strong><span>Zadowolonych klientów</span></div>';
    $html .= '<div class="pt24-home__stat"><strong>5 000+</strong><span>Zweryfikowanych firm</span></div>';
    $html .= '<div class="pt24-home__stat"><strong>24 h</strong><span>Szybka odpowiedź od firm</span></div>';
    $html .= '<div class="pt24-home__stat"><strong>100%</strong><span>Bezpiecznie i bez zobowiązań</span></div>';
    $html .= '</div>';
    $html .= '</section>';

    // === FOR COMPANIES ===
    $html .= '<section class="pt24-home__forfirms">';
    $html .= '<div class="pt24-home__forfirms-content">';
    $html .= '<h2>Dla firm i wykonawców</h2>';
    $html .= '<p>Zdobądź nowych klientów i rozwijaj swój biznes</p>';
    $html .= '<a class="pt24-btn pt24-btn--primary" href="' . esc_url( home_url( '/dla-firm/' ) ) . '">DOŁĄCZ DO NAS →</a>';
    $html .= '<p class="pt24-home__forfirms-note">Zarejestruj firmę i odbieraj zapytania!</p>';
    $html .= '</div>';
    $html .= '</section>';

    // === TESTIMONIALS ===
    $html .= '<section class="pt24-home__testimonials">';
    $html .= '<h2>Co mówią klienci</h2>';
    $html .= '<div class="pt24-home__testimonials-header">';
    $html .= '<div class="pt24-home__rating"><span class="pt24-home__stars">★★★★★</span> <strong>4,9/5</strong><br><small>Na podstawie 2 500+ opinii</small></div>';
    $html .= '</div>';
    $html .= '<div class="pt24-home__reviews">';
    $html .= '<div class="pt24-home__review">'
        . '<div class="pt24-home__review-header"><strong>Katarzyna K.</strong><span>Warszawa</span><span class="pt24-home__stars">★★★★★</span></div>'
        . '<p>Szybko, sprawnie i bezproblemowo. Otrzymałam kilka ofert w 20 minut!</p>'
        . '</div>';
    $html .= '<div class="pt24-home__review">'
        . '<div class="pt24-home__review-header"><strong>Marek T.</strong><span>Kraków</span><span class="pt24-home__stars">★★★★★</span></div>'
        . '<p>Znalazłem świetnego fachowca w dobrej cenie. Polecam!</p>'
        . '</div>';
    $html .= '<div class="pt24-home__review">'
        . '<div class="pt24-home__review-header"><strong>Piotr W.</strong><span>Wrocław</span><span class="pt24-home__stars">★★★★★</span></div>'
        . '<p>Wszystko profesjonalnie, od zapytania po realizację.</p>'
        . '</div>';
    $html .= '</div>';
    $html .= '</section>';

    // === FINAL CTA ===
    $html .= '<section class="pt24-cta-band">';
    $html .= '<h2>Nie trać czasu na szukanie!</h2>';
    $html .= '<p>Wyślij zapytanie i otrzymaj oferty od sprawdzonych fachowców.</p>';
    $html .= '<p><a class="pt24-btn pt24-btn--primary pt24-btn--lg" href="' . $dodaj_url . '">DODAJ ZAPYTANIE TERAZ →</a></p>';
    $html .= '</section>';

    $html .= '</div>';

    return $html;
}


function pt24_seed_pages(): array {
    $ids = array();

    $ids['home'] = pt24_seed_page( 'strona-glowna', 'PT24.PRO — Portal firm i leadów', pt24_seed_home_content() );

    // Add inquiry page (CTA target).
    $ids['dodaj-zapytanie'] = pt24_seed_page(
        'dodaj-zapytanie',
        'Dodaj zapytanie — PT24.PRO',
        '<!-- pt24-inquiry-form -->'
    );

    // Search / Finder page.
    $ids['szukaj'] = pt24_seed_page(
        'szukaj',
        'Szukaj fachowca — PT24.PRO',
        '<!-- pt24-search-finder -->'
    );

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
        // Legacy categories (kept for existing posts)
        'porady'         => 'Porady',
        'koszty'         => 'Koszty i cennik',
        // Blog Engine categories (all 10)
        'poradniki'      => 'Poradniki',
        'awarie'         => 'Awarie',
        'jak-zrobic'     => 'Jak zrobić',
        'rankingi'       => 'Rankingi',
        'pt24-24h'       => '24h',
        'bezpieczenstwo' => 'Bezpieczeństwo',
        'sezonowe'       => 'Sezonowe',
        'problemy'       => 'Najczęstsze problemy',
        'lokalne'        => 'Lokalne poradniki',
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
        array( 'pre' => 'FachowcyPro',   'desc' => 'Wielobranżowa ekipa z wieloletnim doświadczeniem.' ),
        array( 'pre' => 'Serwis24',       'desc' => 'Lokalny serwis dostępny od ręki, także w nagłych awariach.' ),
        array( 'pre' => 'MasterFix',      'desc' => 'Specjaliści od remontów, instalacji i nowoczesnych systemów grzewczych.' ),
        array( 'pre' => 'ProTeam',        'desc' => 'Certyfikowana ekipa z uprawnieniami SEP, gazowymi i budowlanymi.' ),
        array( 'pre' => 'RapidService',   'desc' => 'Błyskawiczna reakcja na awarie — zazwyczaj na miejscu w ciągu godziny.' ),
        array( 'pre' => 'ExpertBud',      'desc' => 'Kompleksowe usługi budowlane i instalacyjne z pisemną gwarancją jakości.' ),
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
