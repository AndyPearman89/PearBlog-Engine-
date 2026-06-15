<?php
/**
 * Template Name: Poradnik.PRO - Poradniki (Archive)
 * Description: Articles/guides archive — filterable listing of all poradniki.
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
    <meta name="description" content="Poradniki — baza wiedzy na temat prawa, finansów, budownictwa, energii i więcej. Zrozum problem zanim podejmiesz decyzję.">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { DEFAULT: '#2563EB', dark: '#1D4ED8', light: '#DBEAFE' },
                        accent: { DEFAULT: '#F59E0B', dark: '#D97706' },
                        surface: { DEFAULT: '#F8FAFC', alt: '#F1F5F9' }
                    },
                    fontFamily: {
                        display: ['Poppins', 'system-ui', 'sans-serif'],
                        body: ['Inter', 'system-ui', 'sans-serif']
                    }
                }
            }
        };
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@700;800&display=swap" rel="stylesheet">
    <?php wp_head(); ?>
</head>
<body <?php body_class('bg-white text-slate-900 antialiased font-body'); ?>>
<?php wp_body_open(); ?>

<div class="min-h-screen">
    <!-- HEADER -->
    <header class="sticky top-0 z-50 border-b border-slate-100 bg-white/95 backdrop-blur-md">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-3 sm:px-6 lg:px-8">
            <a href="<?php echo esc_url(home_url('/')); ?>" class="font-display text-xl font-bold tracking-tight">
                <span class="text-brand">Poradnik</span><span class="text-slate-900">.PRO</span>
            </a>
            <nav class="hidden items-center gap-6 text-sm font-medium text-slate-600 lg:flex">
                <a href="<?php echo esc_url(PearBlog_Poradnik_Pro_Routing::url('articles')); ?>" class="text-brand font-semibold">Poradniki</a>
                <a href="<?php echo esc_url(PearBlog_Poradnik_Pro_Routing::url('specialists')); ?>" class="hover:text-brand">Specjaliści</a>
                <a href="<?php echo esc_url(PearBlog_Poradnik_Pro_Routing::url('calculators')); ?>" class="hover:text-brand">Kalkulatory</a>
                <a href="<?php echo esc_url(PearBlog_Poradnik_Pro_Routing::url('questions')); ?>" class="hover:text-brand">Pytania</a>
                <a href="<?php echo esc_url(PearBlog_Poradnik_Pro_Routing::url('ai-advisor')); ?>" class="hover:text-brand">AI Doradca</a>
            </nav>
        </div>
    </header>

    <!-- HERO -->
    <section class="bg-gradient-to-br from-brand-light via-white to-white py-12 sm:py-16">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <h1 class="font-display text-3xl font-bold tracking-tight sm:text-4xl">📘 Poradniki</h1>
            <p class="mt-3 max-w-2xl text-lg text-slate-600">Zrozum temat, zanim wydasz pieniądze. Sprawdzone porady od ekspertów.</p>

            <!-- Search / Filter -->
            <div class="mt-8 flex flex-col gap-4 sm:flex-row">
                <div class="relative flex-1">
                    <input type="text" id="poradniki-search" placeholder="Szukaj poradnika..." class="w-full rounded-xl border border-slate-200 bg-white py-3 pl-11 pr-4 text-sm shadow-sm focus:border-brand focus:outline-none focus:ring-2 focus:ring-brand/20">
                    <svg class="absolute left-3.5 top-3.5 h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <select id="poradniki-category" class="rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm focus:border-brand focus:outline-none">
                    <option value="">Wszystkie kategorie</option>
                    <?php foreach (PearBlog_Poradnik_Pro_Routing::get_categories() as $slug => $name) : ?>
                        <option value="<?php echo esc_attr($slug); ?>"><?php echo esc_html($name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </section>

    <!-- ARTICLES GRID -->
    <section class="py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div id="poradniki-grid" class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <?php
                $args = [
                    'post_type'      => 'post',
                    'posts_per_page' => 12,
                    'post_status'    => 'publish',
                    'orderby'        => 'date',
                    'order'          => 'DESC',
                ];
                $query = new WP_Query($args);

                if ($query->have_posts()) :
                    while ($query->have_posts()) : $query->the_post();
                ?>
                <article class="group rounded-2xl border border-slate-100 bg-white p-5 shadow-sm transition hover:shadow-md">
                    <?php if (has_post_thumbnail()) : ?>
                        <div class="mb-4 overflow-hidden rounded-xl">
                            <?php the_post_thumbnail('medium_large', ['class' => 'h-44 w-full object-cover transition group-hover:scale-105']); ?>
                        </div>
                    <?php endif; ?>
                    <div class="flex items-center gap-2 text-xs text-slate-500">
                        <time datetime="<?php echo esc_attr(get_the_date('Y-m-d')); ?>"><?php echo esc_html(get_the_date()); ?></time>
                        <?php
                        $cats = get_the_category();
                        if (!empty($cats)) :
                        ?>
                            <span class="rounded-full bg-brand-light px-2 py-0.5 text-brand font-medium"><?php echo esc_html($cats[0]->name); ?></span>
                        <?php endif; ?>
                    </div>
                    <h2 class="mt-2 font-display text-lg font-bold leading-snug group-hover:text-brand">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h2>
                    <p class="mt-2 line-clamp-2 text-sm text-slate-600"><?php echo esc_html(get_the_excerpt()); ?></p>
                </article>
                <?php
                    endwhile;
                    wp_reset_postdata();
                else :
                ?>
                <div class="col-span-full py-12 text-center text-slate-500">
                    <p class="text-lg">Brak poradników do wyświetlenia.</p>
                    <p class="mt-2 text-sm">Sprawdź ponownie za chwilę — nowe treści pojawiają się regularnie.</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($query->max_num_pages > 1) : ?>
            <nav class="mt-10 flex justify-center gap-2">
                <?php
                echo paginate_links([
                    'total'     => $query->max_num_pages,
                    'prev_text' => '← Poprzednie',
                    'next_text' => 'Następne →',
                    'type'      => 'list',
                ]);
                ?>
            </nav>
            <?php endif; ?>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="border-t border-slate-100 bg-slate-50 py-10">
        <div class="mx-auto max-w-7xl px-4 text-center text-sm text-slate-500 sm:px-6 lg:px-8">
            <p>&copy; <?php echo esc_html(gmdate('Y')); ?> Poradnik.PRO — od problemu do decyzji.</p>
        </div>
    </footer>
</div>

<?php wp_footer(); ?>
</body>
</html>
