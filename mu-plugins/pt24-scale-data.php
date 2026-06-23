<?php
/**
 * PT24.PRO — Scale Data Library
 *
 * 80+ Polish cities, 10 services, 20 content variants per template element
 * and service-specific FAQ sets for the AI Factory system.
 *
 * Host-guarded: loaded only on the PT24 install.
 *
 * @package PearBlog\PT24
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PT24_Scale_Data {

	/* =====================================================================
	   CITIES  —  80 key Polish cities
	   slug => ['name', 'prov', 'pop']
	   ===================================================================== */

	public static function cities(): array {
		return [
			// === Mazowieckie ===
			'warszawa'         => [ 'name' => 'Warszawa',         'prov' => 'mazowieckie',        'pop' => 1800000 ],
			'radom'            => [ 'name' => 'Radom',            'prov' => 'mazowieckie',        'pop' => 210000 ],
			'plock'            => [ 'name' => 'Płock',            'prov' => 'mazowieckie',        'pop' => 121000 ],
			'pruszków'         => [ 'name' => 'Pruszków',         'prov' => 'mazowieckie',        'pop' => 63000 ],
			// === Małopolskie ===
			'krakow'           => [ 'name' => 'Kraków',           'prov' => 'małopolskie',        'pop' => 780000 ],
			'tarnow'           => [ 'name' => 'Tarnów',           'prov' => 'małopolskie',        'pop' => 113000 ],
			'nowy-sacz'        => [ 'name' => 'Nowy Sącz',        'prov' => 'małopolskie',        'pop' => 83000 ],
			'oswiecim'         => [ 'name' => 'Oświęcim',         'prov' => 'małopolskie',        'pop' => 37000 ],
			// === Śląskie ===
			'katowice'         => [ 'name' => 'Katowice',         'prov' => 'śląskie',            'pop' => 300000 ],
			'sosnowiec'        => [ 'name' => 'Sosnowiec',        'prov' => 'śląskie',            'pop' => 205000 ],
			'gliwice'          => [ 'name' => 'Gliwice',          'prov' => 'śląskie',            'pop' => 185000 ],
			'zabrze'           => [ 'name' => 'Zabrze',           'prov' => 'śląskie',            'pop' => 175000 ],
			'bielsko-biala'    => [ 'name' => 'Bielsko-Biała',    'prov' => 'śląskie',            'pop' => 172000 ],
			'bytom'            => [ 'name' => 'Bytom',            'prov' => 'śląskie',            'pop' => 155000 ],
			'rybnik'           => [ 'name' => 'Rybnik',           'prov' => 'śląskie',            'pop' => 141000 ],
			'ruda-slaska'      => [ 'name' => 'Ruda Śląska',      'prov' => 'śląskie',            'pop' => 140000 ],
			'tychy'            => [ 'name' => 'Tychy',            'prov' => 'śląskie',            'pop' => 130000 ],
			'dabrowa-gornicza' => [ 'name' => 'Dąbrowa Górnicza', 'prov' => 'śląskie',            'pop' => 121000 ],
			'chorzow'          => [ 'name' => 'Chorzów',          'prov' => 'śląskie',            'pop' => 109000 ],
			'jaworzno'         => [ 'name' => 'Jaworzno',         'prov' => 'śląskie',            'pop' => 91000 ],
			'jastrzebie-zdroj' => [ 'name' => 'Jastrzębie-Zdrój', 'prov' => 'śląskie',            'pop' => 89000 ],
			'myslowice'        => [ 'name' => 'Mysłowice',        'prov' => 'śląskie',            'pop' => 74000 ],
			'siemianowice'     => [ 'name' => 'Siemianowice Śl.', 'prov' => 'śląskie',            'pop' => 66000 ],
			'zory'             => [ 'name' => 'Żory',             'prov' => 'śląskie',            'pop' => 62000 ],
			// === Dolnośląskie ===
			'wroclaw'          => [ 'name' => 'Wrocław',          'prov' => 'dolnośląskie',       'pop' => 640000 ],
			'walbrzych'        => [ 'name' => 'Wałbrzych',        'prov' => 'dolnośląskie',       'pop' => 111000 ],
			'legnica'          => [ 'name' => 'Legnica',          'prov' => 'dolnośląskie',       'pop' => 98000 ],
			'lubin'            => [ 'name' => 'Lubin',            'prov' => 'dolnośląskie',       'pop' => 71000 ],
			'jelenia-gora'     => [ 'name' => 'Jelenia Góra',     'prov' => 'dolnośląskie',       'pop' => 80000 ],
			// === Wielkopolskie ===
			'poznan'           => [ 'name' => 'Poznań',           'prov' => 'wielkopolskie',      'pop' => 540000 ],
			'kalisz'           => [ 'name' => 'Kalisz',           'prov' => 'wielkopolskie',      'pop' => 102000 ],
			'konin'            => [ 'name' => 'Konin',            'prov' => 'wielkopolskie',      'pop' => 74000 ],
			'leszno'           => [ 'name' => 'Leszno',           'prov' => 'wielkopolskie',      'pop' => 63000 ],
			'gniezno'          => [ 'name' => 'Gniezno',          'prov' => 'wielkopolskie',      'pop' => 68000 ],
			// === Pomorskie ===
			'gdansk'           => [ 'name' => 'Gdańsk',           'prov' => 'pomorskie',          'pop' => 470000 ],
			'gdynia'           => [ 'name' => 'Gdynia',           'prov' => 'pomorskie',          'pop' => 247000 ],
			'slupsk'           => [ 'name' => 'Słupsk',           'prov' => 'pomorskie',          'pop' => 90000 ],
			'sopot'            => [ 'name' => 'Sopot',            'prov' => 'pomorskie',          'pop' => 35000 ],
			// === Zachodniopomorskie ===
			'szczecin'         => [ 'name' => 'Szczecin',         'prov' => 'zachodniopomorskie', 'pop' => 410000 ],
			'koszalin'         => [ 'name' => 'Koszalin',         'prov' => 'zachodniopomorskie', 'pop' => 107000 ],
			'stargard'         => [ 'name' => 'Stargard',         'prov' => 'zachodniopomorskie', 'pop' => 70000 ],
			// === Łódzkie ===
			'lodz'             => [ 'name' => 'Łódź',             'prov' => 'łódzkie',            'pop' => 680000 ],
			'piotrkow-tryb'    => [ 'name' => 'Piotrków Tryb.',   'prov' => 'łódzkie',            'pop' => 74000 ],
			'zgierz'           => [ 'name' => 'Zgierz',           'prov' => 'łódzkie',            'pop' => 57000 ],
			// === Kujawsko-Pomorskie ===
			'bydgoszcz'        => [ 'name' => 'Bydgoszcz',        'prov' => 'kujawsko-pomorskie', 'pop' => 360000 ],
			'torun'            => [ 'name' => 'Toruń',            'prov' => 'kujawsko-pomorskie', 'pop' => 200000 ],
			'wloclawek'        => [ 'name' => 'Włocławek',        'prov' => 'kujawsko-pomorskie', 'pop' => 110000 ],
			'grudziadz'        => [ 'name' => 'Grudziądz',        'prov' => 'kujawsko-pomorskie', 'pop' => 96000 ],
			// === Lubelskie ===
			'lublin'           => [ 'name' => 'Lublin',           'prov' => 'lubelskie',          'pop' => 340000 ],
			'zamosc'           => [ 'name' => 'Zamość',           'prov' => 'lubelskie',          'pop' => 64000 ],
			'chelm'            => [ 'name' => 'Chełm',            'prov' => 'lubelskie',          'pop' => 62000 ],
			// === Podkarpackie ===
			'rzeszow'          => [ 'name' => 'Rzeszów',          'prov' => 'podkarpackie',       'pop' => 200000 ],
			'przemysl'         => [ 'name' => 'Przemyśl',         'prov' => 'podkarpackie',       'pop' => 60000 ],
			'stalowa-wola'     => [ 'name' => 'Stalowa Wola',     'prov' => 'podkarpackie',       'pop' => 61000 ],
			// === Podlaskie ===
			'bialystok'        => [ 'name' => 'Białystok',        'prov' => 'podlaskie',          'pop' => 300000 ],
			'suwalki'          => [ 'name' => 'Suwałki',          'prov' => 'podlaskie',          'pop' => 70000 ],
			'lomza'            => [ 'name' => 'Łomża',            'prov' => 'podlaskie',          'pop' => 63000 ],
			// === Świętokrzyskie ===
			'kielce'           => [ 'name' => 'Kielce',           'prov' => 'świętokrzyskie',     'pop' => 195000 ],
			'ostrowiec-sw'     => [ 'name' => 'Ostrowiec Św.',    'prov' => 'świętokrzyskie',     'pop' => 70000 ],
			// === Warmińsko-Mazurskie ===
			'olsztyn'          => [ 'name' => 'Olsztyn',          'prov' => 'warmińsko-mazurskie','pop' => 173000 ],
			'elblag'           => [ 'name' => 'Elbląg',           'prov' => 'warmińsko-mazurskie','pop' => 118000 ],
			// === Opolskie ===
			'opole'            => [ 'name' => 'Opole',            'prov' => 'opolskie',           'pop' => 130000 ],
			// === Lubuskie ===
			'zielona-gora'     => [ 'name' => 'Zielona Góra',     'prov' => 'lubuskie',           'pop' => 140000 ],
			'gorzow-wlkp'      => [ 'name' => 'Gorzów Wlkp.',    'prov' => 'lubuskie',           'pop' => 125000 ],
		];
	}

	/* =====================================================================
	   SERVICES  —  10 usług (nadzbiór istniejących 6)
	   ===================================================================== */

	public static function services(): array {
		return [
			'hydraulik'          => [ 'name' => 'Hydraulik',              'icon' => 'droplet',     'long_tail' => [ 'awaryjny-hydraulik', 'hydraulik-24h', 'usuwanie-awarii-wod-kan' ] ],
			'elektryk'           => [ 'name' => 'Elektryk',               'icon' => 'zap',         'long_tail' => [ 'elektryk-uprawnienia-sep', 'pomiary-elektryczne', 'elektryk-24h' ] ],
			'mechanik'           => [ 'name' => 'Mechanik samochodowy',   'icon' => 'wrench',      'long_tail' => [ 'mobilny-mechanik', 'diagnostyka-komputerowa', 'mechanik-24h' ] ],
			'fotowoltaika'       => [ 'name' => 'Fotowoltaika',           'icon' => 'grid',        'long_tail' => [ 'montaz-paneli-pv', 'fotowoltaika-dofinansowanie', 'audyt-pv' ] ],
			'pompa-ciepla'       => [ 'name' => 'Pompa ciepła',           'icon' => 'thermometer', 'long_tail' => [ 'pompa-ciepla-montaz', 'pompa-ciepla-serwis', 'pompa-ciepla-dofinansowanie' ] ],
			'remont-lazienki'    => [ 'name' => 'Remont łazienki',        'icon' => 'home',        'long_tail' => [ 'glazurnik', 'bialy-montaz', 'hydroizolacja' ] ],
			'laweta'             => [ 'name' => 'Laweta / pomoc drogowa', 'icon' => 'clock',       'long_tail' => [ 'pomoc-drogowa-24h', 'holowanie-samochodu', 'laweta-tania' ] ],
			'wulkanizacja'       => [ 'name' => 'Wulkanizacja',           'icon' => 'wrench',      'long_tail' => [ 'wymiana-opon', 'wulkanizacja-mobilna', 'wulkanizacja-24h' ] ],
			'klimatyzacja'       => [ 'name' => 'Klimatyzacja',           'icon' => 'thermometer', 'long_tail' => [ 'montaz-klimatyzacji', 'serwis-klimatyzacji', 'czyszczenie-klimatyzacji' ] ],
			'instalacje-gazowe'  => [ 'name' => 'Instalacje gazowe',      'icon' => 'zap',         'long_tail' => [ 'przeglad-pieca-gazowego', 'podlaczenie-gazu', 'gazownik-certyfikowany' ] ],
		];
	}

	/* =====================================================================
	   VARIANT SELECTOR
	   Deterministically picks 0-19 based on city+service hash.
	   Same combo → same variant every time (consistent content).
	   ===================================================================== */

	public static function select_variant( string $city, string $service ): int {
		return abs( crc32( $city . '|' . $service ) ) % 20;
	}

	/* =====================================================================
	   CONTENT VARIANT  —  all elements for a given page
	   ===================================================================== */

	public static function content_variant( string $service, string $city ): array {
		$v    = self::select_variant( $city, $service );
		$svc  = self::services()[ $service ] ?? [ 'name' => $service ];
		$cty  = self::cities()[ $city ] ?? [ 'name' => $city ];
		$sn   = $svc['name'];
		$cn   = $cty['name'];

		// FAQ: use $v divided into 4 sets (0-4, 5-9, 10-14, 15-19)
		$faq_set = (int) floor( $v / 5 );

		return [
			'service_name' => $sn,
			'city_name'    => $cn,
			'province'     => $cty['prov'] ?? '',
			'hero_lead'    => sprintf( self::hero_leads()[ $v ], $sn, $cn ),
			'hero_badge'   => sprintf( self::hero_badges()[ $v ], $sn, $cn ),
			'scope_h2'     => sprintf( self::scope_h2s()[ $v ], $sn, $cn ),
			'why_h2'       => sprintf( self::why_h2s()[ $v ], $cn ),
			'cta_text'     => self::cta_texts()[ $v ],
			'cta_lead'     => sprintf( self::cta_leads()[ $v ], $sn, $cn ),
			'faq'          => self::faq_set( $service, $faq_set ),
		];
	}

	/* =====================================================================
	   HERO LEADS  (20 variants — fill: %1$s = service, %2$s = city)
	   ===================================================================== */

	private static function hero_leads(): array {
		return [
			'Potrzebujesz dobrego %1$s w %2$s? Sprawdź opinie i zadzwoń bez pośredników.',
			'Szukasz sprawdzonego %1$s w %2$s? Wiesz, że zły wybór może kosztować kilka razy więcej?',
			'%1$s w %2$s — ale jak znaleźć kogoś, komu naprawdę warto zaufać?',
			'Awaria zawsze przychodzi w najgorszym momencie. Dobry %1$s w %2$s powinien być pod ręką.',
			'Zanim zadzwonisz do losowej firmy z internetu, sprawdź polecanych fachowców PT24 w %2$s.',
			'W %2$s działa wielu %1$s-ów — ale tylko część z nich wykonuje pracę terminowo i za ustaloną cenę.',
			'Dobry %1$s w %2$s przyjeżdża na czas, wycenia uczciwie i zostawia porządek po sobie.',
			'Nie trać czasu na przeglądanie dziesiątek ofert. Znajdź sprawdzonego %1$s w %2$s w PT24.',
			'Zlecenie dla %1$s w %2$s? Porównaj fachowców, przeczytaj opinie i zamów wycenę — bezpłatnie.',
			'Problemy z instalacją w %2$s zdarzają się w każdym domu. Ważne, żeby mieć pod ręką kontakt do rzetelnego %1$s.',
			'Poszukujesz %1$s w %2$s z dobrymi opiniami? PT24 weryfikuje fachowców — zero przypadkowych firm.',
			'Szybka i solidna usługa %1$s w %2$s? To możliwe — jeśli wiesz, gdzie szukać.',
			'Mieszkasz w %2$s i potrzebujesz %1$s? Sprawdź, ile kosztuje i kto robi to najlepiej.',
			'Zamiast szukać sam, zleć znalezienie %1$s w %2$s platformie, która sprawdziła już setki fachowców.',
			'Cena usług %1$s w %2$s zależy od zakresu — ale tylko rzetelna wycena pokaże Ci prawdziwy koszt.',
			'Każde zlecenie w PT24 trafia do zweryfikowanych %1$s-ów z %2$s. Bez nieznajomych z ulicy.',
			'Dobre wykonanie, uczciwa cena, terminowy przyjazd — tak powinien wyglądać %1$s w %2$s.',
			'Najczęstszy błąd przy szukaniu %1$s w %2$s? Wybór po samej cenie. Sprawdź też opinie.',
			'%2$s, konkretne zlecenie, potrzeba szybkiej decyzji — PT24 łączy Cię z %1$s w kilka minut.',
			'Zadzwoń do sprawdzonego %1$s w %2$s. Bezpłatna wycena, bez zobowiązań, bez niespodzianek.',
		];
	}

	/* =====================================================================
	   HERO BADGES (20 variants — fill: %1$s = service, %2$s = city)
	   ===================================================================== */

	private static function hero_badges(): array {
		return [
			'%1$s · %2$s',
			'Usługa: %1$s · %2$s',
			'%2$s · %1$s dostępny',
			'Lokalny %1$s · %2$s',
			'%1$s w %2$s',
			'PT24 · %1$s · %2$s',
			'Fachowiec · %1$s · %2$s',
			'%2$s — %1$s',
			'Najlepszy %1$s · %2$s',
			'Sprawdzony %1$s · %2$s',
			'%1$s — szybko · %2$s',
			'%2$s: %1$s na telefon',
			'Polecany %1$s · %2$s',
			'%1$s z gwarancją · %2$s',
			'24h · %1$s · %2$s',
			'%1$s na miejscu · %2$s',
			'Zweryfikowany %1$s · %2$s',
			'Bez prowizji · %1$s · %2$s',
			'%2$s · Zamów %1$s',
			'%1$s · bezpłatna wycena · %2$s',
		];
	}

	/* =====================================================================
	   SCOPE H2  (20 variants — fill: %1$s = service, %2$s = city)
	   ===================================================================== */

	private static function scope_h2s(): array {
		return [
			'Zakres usług %1$s w %2$s',
			'Co obejmuje usługa %1$s w %2$s?',
			'Jakie prace wykonuje %1$s z %2$s?',
			'Pełny zakres — %1$s %2$s',
			'%1$s w %2$s — co możesz zlecić?',
			'Zakres zlecenia dla %1$s z %2$s',
			'Co zrobi dla Ciebie %1$s w %2$s?',
			'Typowe prace: %1$s, %2$s',
			'Specjalizacja: %1$s w %2$s i okolicach',
			'Czym zajmuje się %1$s w %2$s?',
			'Zlecenia dla %1$s z okolic %2$s',
			'Usługi w pakiecie: %1$s, %2$s',
			'Co wchodzi w zakres: %1$s %2$s',
			'Prace na zlecenie — %1$s, %2$s',
			'Oferta %1$s działającego w %2$s',
			'Rodzaje zleceń: %1$s w %2$s',
			'%1$s z %2$s — pełna lista prac',
			'Szczegółowy zakres: %1$s %2$s',
			'Co zleciłem %1$s w %2$s — przykłady',
			'Portfolio usług: %1$s, %2$s',
		];
	}

	/* =====================================================================
	   WHY H2  (20 variants — fill: %1$s = city)
	   ===================================================================== */

	private static function why_h2s(): array {
		return [
			'Dlaczego warto skorzystać z PT24 w %1$s?',
			'Co wyróżnia fachowców PT24 w %1$s?',
			'Dlaczego klienci z %1$s wybierają PT24?',
			'PT24 w %1$s — co zyskujesz?',
			'Zalety zamawiania przez PT24 w %1$s',
			'Nasi fachowcy w %1$s — 4 powody do zaufania',
			'Bezpieczna usługa w %1$s dzięki PT24',
			'Jak PT24 chroni klientów z %1$s?',
			'PT24 gwarantuje — co dokładnie w %1$s?',
			'Opinie klientów z %1$s mówią same za siebie',
			'Co sprawdzamy przed dołączeniem fachowca z %1$s?',
			'5 powodów, żeby nie szukać samodzielnie w %1$s',
			'Bezpłatna wycena w %1$s — i co dalej?',
			'Różnica między PT24 a tablicą ogłoszeń dla %1$s',
			'Platforma zaufania dla usługodawców z %1$s',
			'Jak działamy dla klientów z %1$s?',
			'PT24 w %1$s — jakość zamiast przypadku',
			'Weryfikacja przed wizytą: co sprawdzamy w %1$s?',
			'PT24 to nie agencja — to narzędzie dla klientów z %1$s',
			'Klienci z %1$s doceniają PT24 za trzy rzeczy',
		];
	}

	/* =====================================================================
	   CTA TEXTS  (20 variants)
	   ===================================================================== */

	private static function cta_texts(): array {
		return [
			'Zamów bezpłatną wycenę',
			'Zadzwoń i umów wizytę',
			'Wyślij zapytanie teraz',
			'Uzyskaj bezpłatną wycenę',
			'Zamów fachowca online',
			'Poproś o wycenę — 0 zł',
			'Skontaktuj się teraz',
			'Wyślij zlecenie',
			'Zamów wizytę',
			'Opisz problem — wycenimy',
			'Bezpłatna wycena → wyślij',
			'Umów fachowca na jutro',
			'Sprawdź dostępność i zadzwoń',
			'Zapytaj o termin',
			'Złóż zlecenie online',
			'Wyślij opis — odpiszemy szybko',
			'Napisz, zadzwoń lub wyślij',
			'Poproś o kontakt fachowca',
			'Zamów teraz, zapłać po robocie',
			'Sprawdź cenę — to nic nie kosztuje',
		];
	}

	/* =====================================================================
	   CTA LEADS  (20 variants — fill: %1$s = service, %2$s = city)
	   ===================================================================== */

	private static function cta_leads(): array {
		return [
			'Opisz swój problem — skontaktuje się z Tobą sprawdzony %1$s z %2$s.',
			'Wypełnij formularz. %1$s z %2$s oddzwoni w ciągu godziny.',
			'Wyślij zlecenie, a %1$s z %2$s wyceni je bezpłatnie.',
			'Nie szukaj dalej. Polecany %1$s z %2$s jest dostępny już dziś.',
			'Jeden formularz — kilku %1$s-ów z %2$s odpowie na Twoje zlecenie.',
			'Bezpłatna wycena od %1$s z %2$s. Bez zobowiązań, bez ukrytych kosztów.',
			'Napisz, czego potrzebujesz, a %1$s z %2$s dotrze do Ciebie tego samego dnia.',
			'Twój problem z zasięgu %1$s z %2$s? Sprawdź dostępność już teraz.',
			'Zaufaj PT24 — %1$s z %2$s wykonał setki zleceń z pozytywnymi opiniami.',
			'%1$s z %2$s jest dostępny — wyślij zapytanie i umów wycenę na dogodny termin.',
			'Chcesz wiedzieć, ile zapłacisz? Dobry %1$s z %2$s wyceni pracę przed rozpoczęciem.',
			'Wyślij zdjęcie lub opis usterki — %1$s z %2$s odpowie szybko.',
			'Znalezienie dobrego %1$s z %2$s zajmie Ci 2 minuty przez PT24.',
			'%1$s z %2$s z PT24 podpisuje zlecenie — żadnych ustnych umów.',
			'Zbierz wyceny od kilku %1$s-ów z %2$s i wybierz najlepszą ofertę.',
			'Awaria nie czeka — %1$s z %2$s jest dostępny. Zadzwoń teraz.',
			'Napisz do nas, a dopasujemy %1$s z %2$s do Twojego budżetu i terminu.',
			'Dobre usługi %1$s w %2$s to inwestycja. Sprawdź opinie przed decyzją.',
			'Rzetelny %1$s z %2$s: ocena, doświadczenie, uczciwa cena. Zamów przez PT24.',
			'Jeden formularz do kilku sprawdzonych %1$s-ów z %2$s — oszczędź czas i pieniądze.',
		];
	}

	/* =====================================================================
	   FAQ SETS  —  4 zestawy × 5 pytań per usługa
	   ===================================================================== */

	public static function faq_set( string $service, int $set = 0 ): array {
		$all = self::all_faqs();
		$sets = $all[ $service ] ?? $all['_generic'];
		$set  = max( 0, min( $set, count( $sets ) - 1 ) );
		return $sets[ $set ];
	}

	private static function all_faqs(): array {
		return [

			/* ---- HYDRAULIK ---- */
			'hydraulik' => [
				// set 0
				[
					[ 'q' => 'Ile kosztuje hydraulik w Polsce?',          'a' => 'Stawki godzinowe hydraulika wynoszą 80–180 zł/h. Drobne naprawy to 150–400 zł, usunięcie awarii 200–600 zł. Dobry hydraulik zawsze wycenia pracę przed rozpoczęciem.' ],
					[ 'q' => 'Jak szybko przyjedzie hydraulik?',           'a' => 'Awaryjny hydraulik z PT24 zazwyczaj przyjeżdża w ciągu 1–3 godzin. Przy standardowych zleceniach termin ustalasz z fachowcem.' ],
					[ 'q' => 'Czy hydraulik daje gwarancję na pracę?',    'a' => 'Solidni hydraulicy z PT24 udzielają gwarancji na wykonane prace (zwykle 12–24 miesiące). Zawsze proś o pisemne potwierdzenie.' ],
					[ 'q' => 'Co przygotować przed przyjazdem hydraulika?','a' => 'Zlokalizuj główny zawór wody i sprawdź, czy jest sprawny. Opisz objawy usterki — pomoże to w szybszej diagnozie i trafnej wycenie.' ],
					[ 'q' => 'Czy hydraulik może naprawić wszystko?',      'a' => 'Hydraulik zajmuje się instalacją wod-kan, CO i gazową (przy odpowiednich uprawnieniach). Przy rozległych awariach skonsultuje, co można naprawić, a co wymaga wymiany.' ],
				],
				// set 1
				[
					[ 'q' => 'Jak znaleźć dobrego hydraulika?',            'a' => 'Wybierz hydraulika z opiniami, wpisem do rejestru działalności i gotowością do pisemnej wyceny. PT24 weryfikuje każdego fachowca przed dodaniem do platformy.' ],
					[ 'q' => 'Kiedy wymienić rury w domu?',                'a' => 'Rury stalowe warto wymienić po 20–25 latach (korozja). Miedź służy 50+ lat, PEX i PP — podobnie. Przy zakupie starego domu hydraulik oceni stan instalacji podczas przeglądu.' ],
					[ 'q' => 'Co robić przy wycieku z rury?',              'a' => 'Zakręć główny zawór wody, usuń wodę i zadzwoń po awaryjnego hydraulika. Nie próbuj samodzielnie uszczelniać pod ciśnieniem — możesz pogorszyć usterkę.' ],
					[ 'q' => 'Czy montaż umywalki to duże zlecenie?',     'a' => 'Montaż standardowej umywalki zajmuje hydraulikowi 1–2 godziny. Cena zależy od stanu podejścia i konieczności modyfikacji instalacji.' ],
					[ 'q' => 'Jakie uprawnienia powinien mieć hydraulik?', 'a' => 'Do instalacji wod-kan nie są wymagane certyfikaty, ale uprawnienia gazowe są obowiązkowe przy pracach przy instalacji gazowej. Zawsze pytaj o zakres i doświadczenie.' ],
				],
				// set 2
				[
					[ 'q' => 'Co obejmuje przegląd instalacji wod-kan?',   'a' => 'Hydraulik sprawdza szczelność rur, stan zaworów, ciśnienie wody, odpływy i stan podgrzewacza. Przegląd trwa 1–2 godziny.' ],
					[ 'q' => 'Ile kosztuje wymiana pionu kanalizacyjnego?', 'a' => 'Koszt wymiany pionu w bloku lub kamienicy to zazwyczaj 1500–5000 zł — zależy od długości rury i trudności dostępu.' ],
					[ 'q' => 'Hydraulik czy własna naprawa — kiedy warto?', 'a' => 'Wymianę baterii lub uszczelki możesz zrobić sam. Przy pracach w ścianach, pod ciśnieniem lub przy gazie — zawsze wzywaj specjalistę.' ],
					[ 'q' => 'Jak hydraulik lokalizuje wycieki w ścianie?', 'a' => 'Używa kamery endoskopowej, termowizji lub detektora wilgoci. Nowoczesna diagnostyka pozwala odnaleźć usterkę bez kucia całej ściany.' ],
					[ 'q' => 'Co zrobić, gdy bateria w łazience kapie?',   'a' => 'Najczęściej wystarczy wymiana uszczelki lub wkładki ceramicznej (koszt: 50–200 zł). Jeśli bateria jest stara, hydraulik może doradzić wymianę na nową.' ],
				],
				// set 3
				[
					[ 'q' => 'Jak wybrać rodzaj rur do instalacji?',       'a' => 'Rury PP są tanie i trwałe (instalacje CO). PEX-A jest elastyczny — dobry do ogrzewania podłogowego. Miedź wytrzymuje wysokie temperatury. Hydraulik dobierze materiał do instalacji.' ],
					[ 'q' => 'Czy hydraulik zajmuje się ogrzewaniem?',     'a' => 'Tak, hydraulik montuje i serwisuje instalacje CO, grzejniki, ogrzewanie podłogowe i pompy obiegowe. Kotły gazowe wymagają dodatkowych uprawnień gazowych.' ],
					[ 'q' => 'Kiedy wezwać awaryjnego hydraulika?',        'a' => 'Przy pęknięciu rury, zalaniu, braku wody lub gwałtownym wycieku zadzwoń natychmiast. Awaryjny hydraulik z PT24 jest dostępny 24/7.' ],
					[ 'q' => 'Ile trwa remont łazienki przez hydraulika?', 'a' => 'Samo przerobienie instalacji wod-kan w łazience zajmuje 1–3 dni. Przy kompleksowym remoncie hydraulik koordynuje swoje prace z glazurnikiem.' ],
					[ 'q' => 'Jak usunąć zator w kanalizacji?',            'a' => 'Przy lekkim zatorze używa się przepychacza lub chemii. Głęboki zator wymaga przepychania spiralą lub przetykania wodą pod ciśnieniem przez specjalistę.' ],
				],
			],

			/* ---- ELEKTRYK ---- */
			'elektryk' => [
				[
					[ 'q' => 'Ile kosztuje elektryk?',                     'a' => 'Stawka godzinowa elektryka to 90–200 zł/h. Wymiana gniazdka — 80–150 zł, nowy obwód — 300–800 zł, instalacja od zera w mieszkaniu — 5 000–20 000 zł.' ],
					[ 'q' => 'Czy elektryk musi mieć uprawnienia SEP?',    'a' => 'Do większości prac w instalacjach domowych potrzebne są uprawnienia SEP "E" (eksploatacja). Elektryk z uprawnieniami wyda protokół pomiarowy wymagany przy odbiorach i ubezpieczeniach.' ],
					[ 'q' => 'Kiedy wymienić instalację elektryczną?',     'a' => 'Instalacje aluminiowe lub mające więcej niż 30 lat warto wymienić ze względów bezpieczeństwa. Elektryk oceni stan i zasugeruje zakres modernizacji.' ],
					[ 'q' => 'Jak długo trwa instalacja elektryczna w domu?','a' => 'Instalacja od zera w domu 100 m² zajmuje elektrykom 5–10 dni roboczych. Wymiana rozdzielnicy to 1 dzień.' ],
					[ 'q' => 'Co to jest protokół pomiarowy?',             'a' => 'To dokument elektryka z uprawnieniami SEP, potwierdzający poprawność instalacji. Jest wymagany przy odbiorze domu, sprzedaży i przez wiele firm ubezpieczeniowych.' ],
				],
				[
					[ 'q' => 'Skąd wiedzieć, że instalacja jest bezpieczna?','a' => 'Elektryk z uprawnieniami wykonuje pomiary rezystancji izolacji i ciągłości przewodów ochronnych. Pozytywny wynik pomiarów to podstawa bezpiecznej instalacji.' ],
					[ 'q' => 'Czy elektryk zajmuje się montażem kamer?',   'a' => 'Tak, wielu elektryków wykonuje też instalacje alarmowe, monitoring CCTV i smart home. Zapytaj o zakres przy wycenie.' ],
					[ 'q' => 'Co zrobić po przepaleniu bezpiecznika?',     'a' => 'Sprawdź, który obwód jest obciążony. Jeśli bezpiecznik wyskakuje po przywróceniu, masz zwarcie lub przeciążenie — wezwij elektryka. Nie blokuj zabezpieczeń.' ],
					[ 'q' => 'Ile obwodów potrzeba w mieszkaniu 60 m²?',   'a' => 'Standardowo: osobny obwód dla pralki, lodówki, piekarnika, oświetlenia (przynajmniej 2), gniazdek (3–4) i ewentualnie klimatyzacji. Razem 8–12 obwodów.' ],
					[ 'q' => 'Czy elektryk może zainstalować ładowarkę EV?','a' => 'Tak. Ładowarka wallbox wymaga dedykowanego obwodu 32A lub 16A. Elektryk oceni stan rozdzielnicy i dobierze odpowiedni model ładowarki.' ],
				],
				[
					[ 'q' => 'Jak zapobiec porażeniu prądem w domu?',      'a' => 'Sprawna instalacja uziemiająca i wyłącznik różnicowoprądowy (RCD) to podstawa. Elektryk z PT24 sprawdzi, czy Twoja instalacja jest odpowiednio zabezpieczona.' ],
					[ 'q' => 'Czy elektryk poprawi samodzielnie wykonaną instalację?','a' => 'Tak, ale musisz liczyć się z wyższym kosztem — elektryk musi ocenić całość, a samowolka może wymagać przeróbek, by uzyskać protokół pomiarowy.' ],
					[ 'q' => 'Co to jest rozdzielnica i kiedy ją wymienić?','a' => 'Rozdzielnica to skrzynka z bezpiecznikami i wyłącznikami. Warto ją wymienić, gdy ma więcej niż 20 lat, brakuje miejsca na nowe obwody lub jest stara (ceramiczne bezpieczniki).' ],
					[ 'q' => 'Ile kosztuje wymiana rozdzielnicy?',         'a' => 'Wymiana rozdzielnicy w mieszkaniu to koszt 800–2500 zł w zależności od liczby obwodów i rodzaju zabezpieczeń.' ],
					[ 'q' => 'Czy elektryk może zamontować gniazdko USB?', 'a' => 'Tak. Gniazdka z USB wbudowanym ładowarką 5V montuje się tak samo jak standardowe gniazdko. Elektryk dobierze odpowiedni model i zamontuje bez konieczności remontu.' ],
				],
				[
					[ 'q' => 'Jak zmniejszyć rachunki za prąd?',           'a' => 'Elektryk pomoże dobrać taryfy, zainstalować licznik dwustrefowy, zoptymalizować obciążenie i podłączyć panele fotowoltaiczne. Często zwrot inwestycji wynosi 3–5 lat.' ],
					[ 'q' => 'Co to jest TN-C-S i czy muszę to wiedzieć?','a' => 'To rodzaj układu sieciowego — rozdziału przewodów N i PE. Elektryk sprawdzi rodzaj sieci w budynku i doradzi, czy wymagane są zmiany przy modernizacji instalacji.' ],
					[ 'q' => 'Czy elektryk pracuje w weekendy?',           'a' => 'Wielu elektryków z PT24 przyjmuje zlecenia w weekendy. Przy pilnych awariach dostępna jest pomoc 24/7.' ],
					[ 'q' => 'Jak znaleźć zwarcie w instalacji?',          'a' => 'Elektryk używa miernika izolacji i analizatora obwodów. Nowoczesna diagnostyka pozwala zlokalizować usterkę bez rozkuwania ścian.' ],
					[ 'q' => 'Czy mogę zainstalować klimatyzację sam?',    'a' => 'Klimatyzacja wymaga elektryka (dedykowany obwód 16–20A) i technika z certyfikatem F-GAZ do podłączenia czynnika chłodniczego. Robienie tego na własną rękę jest niezgodne z przepisami.' ],
				],
			],

			/* ---- MECHANIK ---- */
			'mechanik' => [
				[
					[ 'q' => 'Ile kosztuje wizyta u mechanika?',           'a' => 'Robocizna mechanika to 100–200 zł/h. Podstawowy przegląd kosztuje 200–400 zł, wymiana oleju 150–300 zł. Skomplikowane naprawy są wyceniane indywidualnie.' ],
					[ 'q' => 'Jak często robić przegląd samochodu?',       'a' => 'Przegląd techniczny co 1–2 lata (zależnie od wieku auta). Serwis oleju co 10 000–15 000 km lub raz w roku. Mechanik wskaże Ci optymalny harmonogram dla Twojego modelu.' ],
					[ 'q' => 'Czy mobilny mechanik jest tańszy?',          'a' => 'Mobilny mechanik zazwyczaj pobiera podobną stawkę, ale oszczędzasz czas i koszty holowania. Idealne rozwiązanie przy awarii w trasie lub braku możliwości dojazdu.' ],
					[ 'q' => 'Co to jest diagnostyka komputerowa?',        'a' => 'Podłączenie czytnika OBD do złącza diagnostycznego pojazdu. Mechanik odczytuje kody błędów silnika, skrzyni, ABS i innych systemów — szybko i precyzyjnie.' ],
					[ 'q' => 'Jak sprawdzić, czy mechanik nie zawyżył ceny?','a' => 'Proś o szczegółową fakturę z wyszczególnieniem części i robocizny. Porównaj cenę z kilkoma warsztatami. Solidny mechanik z PT24 zawsze wycenia przed naprawą.' ],
				],
				[
					[ 'q' => 'Jak wybrać dobrego mechanika?',              'a' => 'Sprawdź opinie, zapytaj o doświadczenie z Twoim modelem i żądaj pisemnej wyceny. Mechanizy z PT24 są weryfikowani i mają potwierdzone kwalifikacje.' ],
					[ 'q' => 'Czy mechanik da gwarancję na naprawę?',      'a' => 'Rzetelny mechanik udziela gwarancji na wykonaną naprawę (zwykle 3–12 miesięcy). Zawsze pytaj o warunki gwarancji przed zleceniem.' ],
					[ 'q' => 'Jakie dokumenty zachować po naprawie?',      'a' => 'Faktura VAT, opis wykonanych prac i ewentualne protokoły gwarancyjne. Dokumenty są ważne przy reklamacji, sprzedaży auta i gwarancji.' ],
					[ 'q' => 'Co robić przy awarii na trasie?',            'a' => 'Zjedź na pobocze, włącz awaryjne i zabezpiecz pojazd trójkątem. Skontaktuj się z mobilnym mechanikiem lub lawetą przez PT24.' ],
					[ 'q' => 'Kiedy lepiej kupić nowe auto niż naprawiać?','a' => 'Gdy koszty naprawy przekraczają 50–60% wartości rynkowej auta, opłacalność jest wątpliwa. Mechanik pomoże ocenić stan techniczny i doradzić.' ],
				],
				[
					[ 'q' => 'Ile trwa wymiana sprzęgła?',                 'a' => 'Wymiana sprzęgła zajmuje mechanikowi 3–8 godzin roboczych zależnie od modelu. Koszt: 800–2500 zł z częściami.' ],
					[ 'q' => 'Kiedy wymienić opony?',                      'a' => 'Opony letnie przy głębokości bieżnika < 1,6 mm, zimowe < 4 mm. Wiek opon powyżej 6–8 lat to też powód do wymiany, nawet przy wyglądającym dobrze bieżniku.' ],
					[ 'q' => 'Co to jest alternator i kiedy go wymienić?', 'a' => 'Alternator ładuje akumulator podczas jazdy. Sygnały awarii: kontrolka akumulatora, słabe reflektory, problem z rozruchem. Wymiana: 400–1200 zł z częściami.' ],
					[ 'q' => 'Ile kosztuje wymiana łożyska koła?',         'a' => 'Koszt wymiany łożyska to 250–600 zł z częściami (za jedno koło). Objaw: buczenie lub huk nasilający się przy zakrętach. Nie zwlekaj — awaria łożyska jest niebezpieczna.' ],
					[ 'q' => 'Co zrobić przed zimą z autem?',              'a' => 'Sprawdź opony zimowe, akumulator, ogrzewanie, płyn do chłodnicy (temperatura zamarzania) i zamki. Mechanik wykona przegląd przedzimowy w 1–2 godziny.' ],
				],
				[
					[ 'q' => 'Jak sprawdzić stan silnika?',                'a' => 'Diagnostyka komputerowa, pomiar kompresji i kontrola płynów. Mechanik oceni stan silnika i wskaże potencjalne problemy zanim staną się kosztownymi awariami.' ],
					[ 'q' => 'Co to są hamulce tarczowe vs bębnowe?',     'a' => 'Tarczowe są efektywniejsze — montowane z przodu i coraz częściej z tyłu. Bębnowe są prostsze i tańsze — spotykane z tyłu w starszych lub tańszych autach.' ],
					[ 'q' => 'Ile kosztuje wymiana płynu hamulcowego?',    'a' => 'Wymiana płynu hamulcowego kosztuje 100–200 zł. Należy ją wykonywać co 2 lata lub 40 000 km — płyn absorbuje wodę i traci właściwości.' ],
					[ 'q' => 'Czy naprawiać turbosprężarkę czy wymienić?', 'a' => 'Przy uszkodzeniu łopatek — wymiana. Przy wycieku oleju z uszczelek — naprawa może być wystarczająca. Mechanik po demontażu oceni stan i poda koszt obu opcji.' ],
					[ 'q' => 'Jak długo trwa diagnoza auta?',              'a' => 'Diagnostyka komputerowa trwa 30–60 minut. Pełna ocena stanu technicznego pojazdu (jazda próbna, oględziny podwozia) to 1–2 godziny.' ],
				],
			],

			/* --- GENERIC (fallback for other services) --- */
			'_generic' => [
				[
					[ 'q' => 'Ile kosztuje ta usługa?',                    'a' => 'Cena zależy od zakresu prac i miasta. Fachowcy z PT24 zawsze wyceniają przed rozpoczęciem — bezpłatnie i bez zobowiązań.' ],
					[ 'q' => 'Jak szybko przyjedzie fachowiec?',           'a' => 'Czas przyjazdu zależy od dostępności. Przy awariach staramy się zapewnić fachowca w ciągu kilku godzin.' ],
					[ 'q' => 'Czy fachowiec daje gwarancję?',              'a' => 'Solidni fachowcy z PT24 udzielają gwarancji na wykonane prace. Zapytaj o warunki przy wycenie.' ],
					[ 'q' => 'Jak przygotować się do wizyty fachowca?',   'a' => 'Opisz problem jak najdokładniej. Zapewnij dostęp do miejsca pracy. Sprawdź z fachowcem zakres i cenę przed rozpoczęciem.' ],
					[ 'q' => 'Dlaczego warto korzystać z PT24?',           'a' => 'PT24 weryfikuje fachowców, zbiera opinie klientów i zapewnia bezpłatną wycenę. Bez niespodzianek, bez nieznajomych z ulicy.' ],
				],
				[
					[ 'q' => 'Jak wybrać dobrego fachowca?',               'a' => 'Opinie, doświadczenie i pisemna wycena to podstawa. PT24 weryfikuje każdego fachowca przed dodaniem do platformy.' ],
					[ 'q' => 'Czy fachowiec przyjedzie w weekend?',        'a' => 'Wielu fachowców z PT24 pracuje w weekendy. Przy awariach dostępna jest pomoc 24/7.' ],
					[ 'q' => 'Co zrobić, gdy jakość usługi nie satysfakcjonuje?','a' => 'Skontaktuj się z nami. PT24 mediuje w sporach i wspomaga klientów w dochodzeniu roszczeń z tytułu gwarancji.' ],
					[ 'q' => 'Czy mogę negocjować cenę?',                  'a' => 'Tak — fachowcy z PT24 podają wyceny ofertowe. Możesz porównać kilka ofert i wybrać najkorzystniejszą.' ],
					[ 'q' => 'Jak płacić za usługę?',                     'a' => 'Fachowcy PT24 akceptują gotówkę i przelewy. Wielu oferuje też płatność kartą lub BLIK. Ustal formę płatności przed wizytą.' ],
				],
				[
					[ 'q' => 'Czy mogę zamówić wycenę online?',            'a' => 'Tak — wyślij formularz na tej stronie lub zadzwoń. Fachowiec odpowie w ciągu godziny w godzinach pracy.' ],
					[ 'q' => 'Jak długo trwa typowe zlecenie?',            'a' => 'Czas realizacji zależy od zakresu. Proste naprawy to kilka godzin, większe projekty — kilka dni. Fachowiec poinformuje o terminie przy wycenie.' ],
					[ 'q' => 'Czy fachowiec przywiezie swoje narzędzia?',  'a' => 'Tak, fachowcy PT24 przyjeżdżają z profesjonalnym sprzętem. Materiały i części ustalane są z klientem przed zleceniem.' ],
					[ 'q' => 'Czy mogę zamówić kilka usług jednocześnie?', 'a' => 'Tak. Opisz wszystkie potrzeby w formularzu — fachowiec lub nasz konsultant pomoże zaplanować kolejność prac.' ],
					[ 'q' => 'Co jeśli fachowiec się spóźni?',            'a' => 'Skontaktuj się z fachowcem lub z PT24. Śledzimy terminowość naszych fachowców i bierzemy to pod uwagę w ocenie.' ],
				],
				[
					[ 'q' => 'Jak zgłosić reklamację?',                    'a' => 'Napisz do nas przez formularz kontaktowy lub e-mail. Każda reklamacja jest rozpatrywana indywidualnie, a fachowcy są rozliczani z jakości.' ],
					[ 'q' => 'Czy fachowiec posprząta po pracy?',          'a' => 'Rzetelni fachowcy zostawiają miejsce pracy w porządku. To jeden z elementów ocenianych w opiniach PT24.' ],
					[ 'q' => 'Czy PT24 świadczy usługi bezpośrednio?',    'a' => 'PT24 jest platformą łączącą klientów z fachowcami. Usługę wykonuje fachowiec — PT24 zapewnia weryfikację i obsługę zamówień.' ],
					[ 'q' => 'Jak PT24 weryfikuje fachowców?',             'a' => 'Sprawdzamy doświadczenie, uprawnienia (gdzie wymagane) i zbieramy opinie po każdym zleceniu. Fachowcy z niską oceną są usuwani z platformy.' ],
					[ 'q' => 'Ile fachowców działa na PT24?',              'a' => 'PT24 współpracuje z tysiącami fachowców w całej Polsce. Staramy się, by w każdym mieście był dostępny przynajmniej jeden sprawdzony specjalista.' ],
				],
			],
		];
	}

	/* =====================================================================
	   ROUTING HELPERS  — used by pt24-landing-cpt.php
	   ===================================================================== */

	/** Returns [slug => 'Display Name'] map for routing allowlist. */
	public static function cities_map(): array {
		return array_map( fn( $c ) => $c['name'], self::cities() );
	}

	/** Returns [slug => 'Display Name'] map for routing allowlist. */
	public static function services_map(): array {
		return array_map( fn( $s ) => $s['name'], self::services() );
	}

	/** Lookup display name for a city slug. */
	public static function city_name( string $slug ): string {
		return self::cities()[ $slug ]['name'] ?? ucfirst( str_replace( '-', ' ', $slug ) );
	}

	/**
	 * City name in Polish locative case (e.g. "w Warszawie", "w Krakowie").
	 * Used in hero lead text on landing pages.
	 */
	public static function city_locative( string $slug ): string {
		$map = [
			'warszawa'          => 'Warszawie',
			'krakow'            => 'Krakowie',
			'lodz'              => 'Łodzi',
			'wroclaw'           => 'Wrocławiu',
			'poznan'            => 'Poznaniu',
			'gdansk'            => 'Gdańsku',
			'szczecin'          => 'Szczecinie',
			'bydgoszcz'         => 'Bydgoszczy',
			'lublin'            => 'Lublinie',
			'katowice'          => 'Katowicach',
			'bialystok'         => 'Białymstoku',
			'gdynia'            => 'Gdyni',
			'czestochowa'       => 'Częstochowie',
			'sosnowiec'         => 'Sosnowcu',
			'radom'             => 'Radomiu',
			'torun'             => 'Toruniu',
			'kielce'            => 'Kielcach',
			'rzeszow'           => 'Rzeszowie',
			'gliwice'           => 'Gliwicach',
			'zabrze'            => 'Zabrzu',
			'bytom'             => 'Bytomiu',
			'bielsko-biala'     => 'Bielsku-Białej',
			'rybnik'            => 'Rybniku',
			'tychy'             => 'Tychach',
			'ruda-slaska'       => 'Rudzie Śląskiej',
			'dabrowa-gornicza'  => 'Dąbrowie Górniczej',
			'chorzow'           => 'Chorzowie',
			'jaworzno'          => 'Jaworznie',
			'jastrzebie-zdroj'  => 'Jastrzębiu-Zdroju',
			'myslowice'         => 'Mysłowicach',
			'siemianowice'      => 'Siemianowicach Śl.',
			'zory'              => 'Żorach',
			'opole'             => 'Opolu',
			'elblag'            => 'Elblągu',
			'plock'             => 'Płocku',
			'walbrzych'         => 'Wałbrzychu',
			'wloclawek'         => 'Włocławku',
			'tarnow'            => 'Tarnowie',
			'zielona-gora'      => 'Zielonej Górze',
			'kalisz'            => 'Kaliszu',
			'legnica'           => 'Legnicy',
			'grudziadz'         => 'Grudziądzu',
			'slupsk'            => 'Słupsku',
			'jelenia-gora'      => 'Jeleniej Górze',
			'olsztyn'           => 'Olsztynie',
			'konin'             => 'Koninie',
			'lubin'             => 'Lubinie',
			'leszno'            => 'Lesznie',
			'gniezno'           => 'Gnieźnie',
			'sopot'             => 'Sopocie',
			'koszalin'          => 'Koszalinie',
			'stargard'          => 'Stargardzie',
			'zgierz'            => 'Zgierzu',
			'piotrkow-tryb'     => 'Piotrkowie Tryb.',
			'pruszków'          => 'Pruszkowie',
			'stalowa-wola'      => 'Stalowej Woli',
			'przemysl'          => 'Przemyślu',
			'zamosc'            => 'Zamościu',
			'chelm'             => 'Chełmie',
			'suwalki'           => 'Suwałkach',
			'lomza'             => 'Łomży',
			'ostrowiec-sw'      => 'Ostrowcu Świętokrzyskim',
			'nowy-sacz'         => 'Nowym Sączu',
			'oswiecim'          => 'Oświęcimiu',
			'gorzow-wlkp'       => 'Gorzowie Wlkp.',
		];
		return isset( $map[ $slug ] ) ? 'w ' . $map[ $slug ] : 'w ' . self::city_name( $slug );
	}

	/**
	 * Preposition phrase for service in Polish (e.g. "hydraulika", "elektryka").
	 * Used in hero lead: "Szukasz {service_prep} w {city_loc}?"
	 */
	public static function service_preposition( string $slug ): string {
		$map = [
			'hydraulik'         => 'hydraulika',
			'elektryk'          => 'elektryka',
			'mechanik'          => 'mechanika samochodowego',
			'fotowoltaika'      => 'instalacji fotowoltaicznej',
			'pompa-ciepla'      => 'pompy ciepła',
			'remont-lazienki'   => 'ekipy do remontu łazienki',
			'laweta'            => 'lawety lub pomocy drogowej',
			'wulkanizacja'      => 'wulkanizacji lub wymiany opon',
			'klimatyzacja'      => 'montażu lub serwisu klimatyzacji',
			'instalacje-gazowe' => 'gazownika z certyfikatem',
		];
		return $map[ $slug ] ?? mb_strtolower( self::service_name( $slug ) );
	}

	/** Lookup display name for a service slug. */
	public static function service_name( string $slug ): string {
		return self::services()[ $slug ]['name'] ?? ucfirst( str_replace( '-', ' ', $slug ) );
	}

	/** Total possible base pages (cities × services). */
	public static function max_pages(): int {
		return count( self::cities() ) * count( self::services() );
	}
}

Tue Jun 23 06:33:42 UTC 2026
