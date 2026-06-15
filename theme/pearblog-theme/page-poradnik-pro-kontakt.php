<?php
/**
 * Template Name: Poradnik.PRO - Kontakt
 * Description: Contact page with inquiry form, support channels, and office info.
 *
 * @package PearBlog
 * @version 5.0.0
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { brand: { DEFAULT: '#2563EB', dark: '#1D4ED8', light: '#DBEAFE' }, accent: '#F59E0B' }, fontFamily: { display: ['Poppins','system-ui','sans-serif'], body: ['Inter','system-ui','sans-serif'] } } } };
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@700;800&display=swap" rel="stylesheet">
    <?php wp_head(); ?>
</head>
<body <?php body_class('bg-white text-slate-900 antialiased font-body'); ?>>
<?php wp_body_open(); ?>

<!-- Breadcrumb -->
<nav class="max-w-4xl mx-auto px-4 py-4 text-sm text-slate-500">
    <a href="/" class="hover:text-brand">Strona główna</a>
    <span class="mx-1">/</span>
    <span class="text-slate-800">Kontakt</span>
</nav>

<!-- Hero -->
<header class="max-w-4xl mx-auto px-4 py-12 text-center">
    <h1 class="font-display text-3xl md:text-4xl font-extrabold text-slate-900 mb-4">Skontaktuj się z nami</h1>
    <p class="text-lg text-slate-600 max-w-xl mx-auto">Masz pytanie, sugestię lub potrzebujesz wsparcia? Jesteśmy tu, żeby pomóc.</p>
</header>

<!-- Contact Channels -->
<section class="max-w-4xl mx-auto px-4 py-8">
    <div class="grid md:grid-cols-3 gap-6">
        <div class="text-center p-6 bg-slate-50 rounded-2xl border border-slate-200">
            <span class="text-3xl block mb-3">📧</span>
            <h3 class="font-semibold mb-2">Email</h3>
            <p class="text-sm text-slate-600 mb-3">Odpowiadamy w ciągu 24h</p>
            <a href="mailto:kontakt@poradnik.pro" class="text-brand font-medium hover:underline">kontakt@poradnik.pro</a>
        </div>
        <div class="text-center p-6 bg-slate-50 rounded-2xl border border-slate-200">
            <span class="text-3xl block mb-3">💬</span>
            <h3 class="font-semibold mb-2">Live Chat</h3>
            <p class="text-sm text-slate-600 mb-3">Pn–Pt, 9:00–17:00</p>
            <button class="text-brand font-medium hover:underline">Otwórz czat</button>
        </div>
        <div class="text-center p-6 bg-slate-50 rounded-2xl border border-slate-200">
            <span class="text-3xl block mb-3">❓</span>
            <h3 class="font-semibold mb-2">FAQ</h3>
            <p class="text-sm text-slate-600 mb-3">Najczęstsze pytania</p>
            <a href="/faq/" class="text-brand font-medium hover:underline">Przejdź do FAQ</a>
        </div>
    </div>
</section>

<!-- Contact Form -->
<section class="max-w-3xl mx-auto px-4 py-12">
    <h2 class="font-display text-2xl font-bold text-center mb-8">Wyślij wiadomość</h2>
    <form id="contact-form" class="space-y-6 bg-slate-50 rounded-2xl p-8 border border-slate-200">
        <div class="grid md:grid-cols-2 gap-6">
            <div>
                <label for="name" class="block text-sm font-medium mb-2">Imię i nazwisko</label>
                <input type="text" id="name" name="name" required class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-brand focus:border-brand outline-none" placeholder="Jan Kowalski">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium mb-2">Email</label>
                <input type="email" id="email" name="email" required class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-brand focus:border-brand outline-none" placeholder="jan@example.com">
            </div>
        </div>
        <div>
            <label for="subject" class="block text-sm font-medium mb-2">Temat</label>
            <select id="subject" name="subject" class="w-full border border-slate-300 rounded-xl px-4 py-3 bg-white focus:ring-2 focus:ring-brand focus:border-brand outline-none">
                <option value="ogolne">Pytanie ogólne</option>
                <option value="wspolpraca">Współpraca / Reklama</option>
                <option value="specjalista">Jestem specjalistą — chcę dołączyć</option>
                <option value="blad">Zgłoszenie błędu</option>
                <option value="inne">Inne</option>
            </select>
        </div>
        <div>
            <label for="message" class="block text-sm font-medium mb-2">Wiadomość</label>
            <textarea id="message" name="message" rows="5" required class="w-full border border-slate-300 rounded-xl px-4 py-3 focus:ring-2 focus:ring-brand focus:border-brand outline-none resize-none" placeholder="Opisz swoje pytanie lub sugestię…"></textarea>
        </div>
        <div class="flex items-start gap-2">
            <input type="checkbox" id="consent" name="consent" required class="mt-1 rounded border-slate-300 text-brand focus:ring-brand">
            <label for="consent" class="text-sm text-slate-600">Wyrażam zgodę na przetwarzanie danych osobowych w celu odpowiedzi na moje zapytanie. <a href="/polityka-prywatnosci/" class="text-brand underline">Polityka prywatności</a></label>
        </div>
        <button type="submit" class="w-full bg-brand text-white font-bold text-lg py-4 rounded-xl hover:bg-brand-dark transition shadow-lg shadow-brand/20">
            📩 Wyślij wiadomość
        </button>
    </form>
</section>

<!-- For Specialists CTA -->
<section class="max-w-4xl mx-auto px-4 py-12 border-t border-slate-100">
    <div class="bg-gradient-to-r from-brand/5 to-blue-50 rounded-2xl p-8 text-center">
        <h2 class="font-display text-xl font-bold mb-3">Jesteś specjalistą?</h2>
        <p class="text-slate-600 mb-6">Dołącz do platformy i odbieraj zapytania od klientów w Twojej okolicy.</p>
        <a href="/dla-specjalistow/" class="inline-flex items-center px-6 py-3 bg-brand text-white font-semibold rounded-xl hover:bg-brand-dark transition">🧑‍💼 Dowiedz się więcej</a>
    </div>
</section>

<?php wp_footer(); ?>
</body>
</html>
