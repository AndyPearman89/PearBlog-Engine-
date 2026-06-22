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

const PT24_SEED_VERSION = '1.0.3';

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
        // Refresh content so seed bumps keep copy in sync, but never clobber edits
        // that a human made after the seed (tracked via _pt24_seeded meta).
        if ( get_post_meta( $existing->ID, '_pt24_seeded', true ) ) {
            wp_update_post(
                array(
                    'ID'           => $existing->ID,
                    'post_title'   => $title,
                    'post_content' => $content,
                )
            );
        }
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
    return (int) $page_id;
}

/**
 * Build the homepage HTML (service x city grid + value props + CTA).
 */
function pt24_seed_home_content(): string {
    $services = array(
        'mechanik'         => 'Mechanik',
        'hydraulik'        => 'Hydraulik',
        'elektryk'         => 'Elektryk',
        'pompa-ciepla'     => 'Pompa ciepła',
        'remont-lazienki'  => 'Remont łazienki',
        'fotowoltaika'     => 'Fotowoltaika',
    );
    $cities = array(
        'warszawa' => 'Warszawa',
        'krakow'   => 'Kraków',
        'wroclaw'  => 'Wrocław',
        'poznan'   => 'Poznań',
        'gdansk'   => 'Gdańsk',
        'katowice' => 'Katowice',
    );

    $html  = '<div class="pt24-home">';
    $html .= '<section class="pt24-home__hero"><h2>Znajdź sprawdzonego fachowca w swojej okolicy</h2>';
    $html .= '<p>Opisz zlecenie raz i otrzymaj do trzech bezpłatnych ofert od lokalnych specjalistów. Bez prowizji, bez zobowiązań.</p></section>';

    $html .= '<section class="pt24-home__values"><ul>';
    $html .= '<li><strong>Zweryfikowani fachowcy</strong> — opinie i oceny realnych klientów.</li>';
    $html .= '<li><strong>Bezpłatna wycena</strong> — porównujesz oferty bez kosztów.</li>';
    $html .= '<li><strong>Szybki kontakt</strong> — pierwsze oferty zwykle w kilka godzin.</li>';
    $html .= '</ul></section>';

    $html .= '<section class="pt24-home__services"><h2>Popularne usługi</h2><div class="pt24-home__cards">';
    foreach ( $services as $sslug => $sname ) {
        $links = array();
        foreach ( $cities as $cslug => $cname ) {
            $links[] = sprintf(
                '<a href="%s">%s</a>',
                esc_url( home_url( "/{$cslug}/{$sslug}/" ) ),
                esc_html( $cname )
            );
        }
        $html .= '<div class="pt24-home__card"><h3>' . esc_html( $sname ) . '</h3><p>'
            . implode( ' · ', $links ) . '</p></div>';
    }
    $html .= '</div></section>';

    $html .= '<section class="pt24-home__how"><h2>Jak to działa?</h2><ol>';
    $html .= '<li>Opisz, czego potrzebujesz.</li><li>Otrzymaj dopasowane oferty.</li>';
    $html .= '<li>Wybierz fachowca i zrealizuj usługę.</li></ol></section>';

    $html .= '<section class="pt24-home__cta"><p><a class="pt24-home__btn" href="' . esc_url( home_url( '/jak-to-dziala/' ) ) . '">Dowiedz się więcej</a></p></section>';
    $html .= '</div>';

    $html .= '<style>'
        . '.pt24-home__hero h2{font-size:2rem;}'
        . '.pt24-home__values ul{list-style:none;padding:0;display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;}'
        . '.pt24-home__values li{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:1rem;}'
        . '.pt24-home__cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1rem;}'
        . '.pt24-home__card{border:1px solid #e2e8f0;border-radius:10px;padding:1rem;background:#fff;}'
        . '.pt24-home__card h3{margin:.2rem 0 .5rem;}'
        . '.pt24-home__card a{color:#2563eb;text-decoration:none;}'
        . '.pt24-home__btn{display:inline-block;background:#f59e0b;color:#1f2937;padding:.8rem 1.4rem;border-radius:8px;font-weight:600;text-decoration:none;}'
        . '</style>';

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
        '<h2>Trzy proste kroki do realizacji zlecenia</h2>'
        . '<p>PT24.PRO to platforma, która łączy osoby szukające usług z lokalnymi, zweryfikowanymi fachowcami. Cały proces jest bezpłatny dla zlecających.</p>'
        . '<h3>1. Opisz zlecenie</h3><p>Wypełnij krótki formularz: wybierz usługę, miasto i opisz zakres prac. Zajmuje to mniej niż minutę.</p>'
        . '<h3>2. Otrzymaj oferty</h3><p>Twoje zapytanie trafia do fachowców działających w Twojej okolicy. W ciągu kilku godzin otrzymujesz dopasowane wyceny.</p>'
        . '<h3>3. Wybierz i zrealizuj</h3><p>Porównujesz ceny, opinie i terminy, a następnie umawiasz się bezpośrednio z wybranym specjalistą. Bez prowizji od PT24.</p>'
        . '<h3>Dlaczego warto?</h3><ul><li>Oszczędzasz czas — nie dzwonisz po kilkunastu numerach.</li>'
        . '<li>Porównujesz konkretne oferty zamiast szukać po omacku.</li>'
        . '<li>Korzystasz z opinii innych klientów.</li></ul>'
    );

    $ids['pricing'] = pt24_seed_page(
        'dla-firm',
        'Dla firm — pozyskuj klientów z PT24',
        '<h2>Otrzymuj zlecenia od klientów z Twojej okolicy</h2>'
        . '<p>Jesteś fachowcem lub prowadzisz firmę usługową? Dołącz do PT24.PRO i odbieraj zapytania od klientów, którzy szukają dokładnie tego, co oferujesz.</p>'
        . '<h3>Pakiety</h3>'
        . '<table><thead><tr><th>Pakiet</th><th>Zakres</th><th>Cena</th></tr></thead><tbody>'
        . '<tr><td><strong>Start</strong></td><td>Profil firmy, do 10 zapytań / mies.</td><td>0 zł</td></tr>'
        . '<tr><td><strong>Profi</strong></td><td>Wyróżnienie, nielimitowane zapytania, statystyki</td><td>149 zł / mies.</td></tr>'
        . '<tr><td><strong>Premium</strong></td><td>Top pozycje, priorytetowe leady, opiekun konta</td><td>349 zł / mies.</td></tr>'
        . '</tbody></table>'
        . '<p>Bez długich umów — pakiet możesz zmienić lub anulować w dowolnym momencie.</p>'
        . '<p><a href="' . esc_url( home_url( '/kontakt/' ) ) . '">Skontaktuj się z nami</a>, aby rozpocząć.</p>'
    );

    $ids['about'] = pt24_seed_page(
        'o-nas',
        'O nas',
        '<h2>PT24.PRO — pomoc techniczna od ręki</h2>'
        . '<p>PT24.PRO powstało, aby uprościć poszukiwanie rzetelnych fachowców. Łączymy klientów z lokalnymi specjalistami z różnych branż — od mechaniki, przez hydraulikę i elektrykę, po remonty i odnawialne źródła energii.</p>'
        . '<p>Naszą misją jest oszczędzić Ci czasu i stresu. Zamiast przeszukiwać dziesiątki ogłoszeń, opisujesz zlecenie raz i otrzymujesz konkretne oferty od sprawdzonych wykonawców.</p>'
        . '<h3>Nasze wartości</h3><ul><li>Przejrzystość — jasne zasady i brak ukrytych kosztów.</li>'
        . '<li>Jakość — weryfikujemy fachowców i zbieramy opinie klientów.</li>'
        . '<li>Lokalność — łączymy Cię ze specjalistami z Twojej okolicy.</li></ul>'
    );

    $ids['contact'] = pt24_seed_page(
        'kontakt',
        'Kontakt',
        '<h2>Skontaktuj się z nami</h2>'
        . '<p>Masz pytanie dotyczące działania serwisu lub współpracy? Napisz do nas — odpowiadamy w dni robocze.</p>'
        . '<ul><li><strong>E-mail:</strong> kontakt@pt24.pro</li>'
        . '<li><strong>Dla firm:</strong> firmy@pt24.pro</li></ul>'
        . '<p>Jeśli szukasz fachowca, najszybszą drogą jest wypełnienie formularza na stronie wybranej usługi — Twoje zapytanie trafi bezpośrednio do specjalistów.</p>'
    );

    $ids['privacy'] = pt24_seed_page(
        'polityka-prywatnosci',
        'Polityka prywatności',
        '<h2>Polityka prywatności</h2>'
        . '<p>Niniejsza polityka opisuje, w jaki sposób PT24.PRO przetwarza dane osobowe użytkowników serwisu zgodnie z RODO.</p>'
        . '<h3>Administrator danych</h3><p>Administratorem danych jest operator serwisu PT24.PRO. Kontakt: kontakt@pt24.pro.</p>'
        . '<h3>Zakres i cel przetwarzania</h3><p>Przetwarzamy dane podane w formularzach (imię, telefon, e-mail, treść zapytania) wyłącznie w celu skojarzenia zapytania z odpowiednimi fachowcami i obsługi zgłoszenia.</p>'
        . '<h3>Podstawa prawna</h3><p>Dane przetwarzamy na podstawie zgody (art. 6 ust. 1 lit. a RODO) oraz w celu realizacji usługi (art. 6 ust. 1 lit. b RODO).</p>'
        . '<h3>Twoje prawa</h3><p>Masz prawo dostępu do danych, ich sprostowania, usunięcia, ograniczenia przetwarzania oraz wniesienia sprzeciwu i skargi do organu nadzorczego.</p>'
        . '<h3>Pliki cookies</h3><p>Serwis wykorzystuje pliki cookies w celach statystycznych i poprawy działania strony. Możesz zarządzać nimi w ustawieniach przeglądarki.</p>'
    );

    $ids['terms'] = pt24_seed_page(
        'regulamin',
        'Regulamin',
        '<h2>Regulamin serwisu PT24.PRO</h2>'
        . '<h3>§1 Postanowienia ogólne</h3><p>Serwis PT24.PRO umożliwia kojarzenie użytkowników poszukujących usług z fachowcami gotowymi je zrealizować.</p>'
        . '<h3>§2 Zasady korzystania</h3><p>Korzystanie z serwisu przez zlecających jest bezpłatne. Wysłanie zapytania nie jest zobowiązaniem do zawarcia umowy.</p>'
        . '<h3>§3 Odpowiedzialność</h3><p>PT24.PRO pełni rolę pośrednika informacyjnego. Umowa o wykonanie usługi zawierana jest bezpośrednio między klientem a fachowcem.</p>'
        . '<h3>§4 Reklamacje</h3><p>Reklamacje dotyczące działania serwisu można zgłaszać na adres kontakt@pt24.pro.</p>'
        . '<h3>§5 Postanowienia końcowe</h3><p>Operator zastrzega sobie prawo do zmiany regulaminu. Aktualna wersja jest zawsze dostępna na tej stronie.</p>'
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
