<?php
/**
 * Template Name: Poradnik.PRO - Pytanie (Single Question)
 * Description: Single question page (/pytanie/{slug})
 *
 * @package PearBlog
 * @subpackage PoradnikPro
 * @version 6.0.0
 */

defined( 'ABSPATH' ) || exit;

require_once get_template_directory() . '/inc/poradnik-pro-shared.php';
require_once get_template_directory() . '/inc/poradnik-pro-seed-data.php';

// Resolve question from routing or WordPress title.
$pp_slug     = get_query_var( 'poradnik_slug', '' );
$pp_question = pp_seed_get_question( $pp_slug );
$pp_questions = pp_seed_questions();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
	<?php pp_pro_shared_styles(); ?>
	<style>
		.question-hero { padding: 40px 0 32px; background: linear-gradient(135deg, #fff7ed 0%, #fef3c7 100%); }
		.question-content { padding: 48px 0 64px; }
		.question-body { max-width: 780px; margin: 0 auto; }
		.q-meta { display: flex; align-items: center; gap: 12px; margin-top: 12px; flex-wrap: wrap; }
		.q-meta-chip { padding: 4px 12px; border-radius: 50px; font-size: 12px; font-weight: 600; background: #f3e8ff; color: var(--purple-primary); }
		.q-meta-text { font-size: 13px; color: var(--gray-500); }
		.q-description { margin-top: 20px; font-size: 15px; color: var(--gray-700); line-height: 1.7; }

		.best-answer { background: #f0fdf4; border: 2px solid #86efac; border-radius: var(--radius-lg); padding: 28px; margin-top: 32px; }
		.best-answer-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 50px; font-size: 12px; font-weight: 700; background: #dcfce7; color: #166534; margin-bottom: 16px; }
		.best-answer-text { font-size: 15px; color: var(--gray-800); line-height: 1.7; }
		.best-answer-author { display: flex; align-items: center; gap: 12px; margin-top: 20px; padding-top: 16px; border-top: 1px solid #bbf7d0; }
		.author-avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--purple-primary); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; }
		.author-name { font-size: 14px; font-weight: 700; color: var(--gray-900); }
		.author-meta { font-size: 12px; color: var(--gray-500); }

		.other-answers { margin-top: 40px; }
		.other-answers-title { font-size: 13px; font-weight: 700; color: var(--gray-500); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 20px; }
		.answer-card { background: #fff; border: 1px solid var(--gray-200); border-radius: var(--radius-md); padding: 20px; margin-bottom: 16px; }
		.answer-card-text { font-size: 14px; color: var(--gray-700); line-height: 1.6; }
		.answer-card-footer { display: flex; align-items: center; gap: 12px; margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--gray-100); }
		.answer-card-author { font-size: 13px; font-weight: 600; color: var(--gray-800); }
		.answer-card-spec { font-size: 12px; color: var(--gray-500); }

		.related-questions { margin-top: 48px; }
		.related-title { font-size: 18px; font-weight: 700; color: var(--gray-900); margin-bottom: 16px; }
		.related-link { display: block; padding: 14px 20px; border: 1px solid var(--gray-200); border-radius: var(--radius-md); font-size: 14px; font-weight: 500; color: var(--gray-800); margin-bottom: 10px; transition: all 0.2s; }
		.related-link:hover { border-color: var(--purple-primary); color: var(--purple-primary); transform: translateX(4px); }

		.cta-section { margin-top: 48px; background: var(--purple-dark); border-radius: var(--radius-xl); padding: 40px; text-align: center; }
		.cta-section h2 { font-size: 20px; font-weight: 700; color: #fff; margin-bottom: 8px; }
		.cta-section p { font-size: 14px; color: rgba(255,255,255,0.7); margin-bottom: 20px; }
		.cta-btn { display: inline-block; padding: 14px 32px; border-radius: 50px; background: var(--orange-cta); color: #fff; font-size: 14px; font-weight: 700; transition: background 0.2s; }
		.cta-btn:hover { background: var(--orange-hover); }
	</style>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<?php pp_pro_header( 'pytania' ); ?>

<!-- QUESTION HERO -->
<section class="question-hero">
	<div class="container">
		<div class="breadcrumb">
			<a href="<?php echo esc_url( home_url( '/' ) ); ?>">Strona główna</a>
			<span class="sep">/</span>
			<a href="<?php echo esc_url( home_url( '/pytania/' ) ); ?>">Pytania</a>
			<span class="sep">/</span>
			<span><?php echo esc_html( $pp_question['category'] ); ?></span>
		</div>
		<h1><?php echo esc_html( $pp_question['title'] ); ?></h1>
		<div class="q-meta">
			<span class="q-meta-chip"><?php echo esc_html( $pp_question['category'] ); ?></span>
			<span class="q-meta-text"><?php echo esc_html( $pp_question['author'] ); ?></span>
			<span class="q-meta-text">&middot; <?php echo esc_html( $pp_question['time'] ); ?></span>
			<span class="q-meta-text">&middot; <?php echo esc_html( $pp_question['answers'] ); ?> odpowiedzi</span>
		</div>
	</div>
</section>

<!-- QUESTION CONTENT -->
<section class="question-content">
	<div class="container">
		<div class="question-body">
			<p class="q-description"><?php echo esc_html( $pp_question['content'] ); ?></p>

			<!-- BEST ANSWER -->
			<div class="best-answer">
				<div class="best-answer-badge">&#10003; Najlepsza odpowiedź</div>
				<div class="best-answer-text"><?php echo esc_html( $pp_question['best_answer']['text'] ); ?></div>
				<div class="best-answer-author">
					<div class="author-avatar"><?php echo esc_html( mb_substr( $pp_question['best_answer']['author'], 0, 1 ) ); ?></div>
					<div>
						<div class="author-name"><?php echo esc_html( $pp_question['best_answer']['author'] ); ?></div>
						<div class="author-meta"><?php echo esc_html( $pp_question['best_answer']['specialty'] ); ?> &middot; &#9733; <?php echo esc_html( $pp_question['best_answer']['rating'] ); ?> &middot; <?php echo esc_html( $pp_question['best_answer']['answers_count'] ); ?> odpowiedzi</div>
					</div>
				</div>
			</div>

			<!-- OTHER ANSWERS -->
			<div class="other-answers">
				<h2 class="other-answers-title">Pozostałe odpowiedzi (<?php echo count( $pp_question['other_answers'] ); ?>)</h2>
				<?php foreach ( $pp_question['other_answers'] as $answer ) : ?>
				<div class="answer-card">
					<div class="answer-card-text"><?php echo esc_html( $answer['text'] ); ?></div>
					<div class="answer-card-footer">
						<span class="answer-card-author"><?php echo esc_html( $answer['author'] ); ?></span>
						<span class="answer-card-spec"><?php echo esc_html( $answer['specialty'] ); ?> &middot; &#9733; <?php echo esc_html( $answer['rating'] ); ?></span>
					</div>
				</div>
				<?php endforeach; ?>
			</div>

			<!-- RELATED QUESTIONS -->
			<div class="related-questions">
				<h2 class="related-title">Powiązane pytania</h2>
				<?php
				$pp_related_count = 0;
				foreach ( $pp_questions as $rq ) :
					if ( $rq['slug'] === $pp_question['slug'] ) {
						continue;
					}
					if ( $pp_related_count >= 4 ) {
						break;
					}
					++$pp_related_count;
				?>
				<a href="<?php echo esc_url( home_url( '/pytanie/' . $rq['slug'] . '/' ) ); ?>" class="related-link"><?php echo esc_html( $rq['title'] ); ?></a>
				<?php endforeach; ?>
			</div>

			<!-- CTA -->
			<div class="cta-section">
				<h2>Masz podobne pytanie?</h2>
				<p>Zadaj pytanie — nasi eksperci odpowiedzą w ciągu 24h.</p>
				<a href="<?php echo esc_url( home_url( '/kontakt/' ) ); ?>" class="cta-btn">Zadaj pytanie &rarr;</a>
			</div>
		</div>
	</div>
</section>

<?php pp_pro_footer(); ?>
<?php wp_footer(); ?>
</body>
</html>
