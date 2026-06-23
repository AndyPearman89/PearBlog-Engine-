<?php
/**
 * PT24 Landing Generator — Admin UI
 *
 * Submenu page under pt24_landing CPT.
 * Integrates with PT24_AI_Factory (AI generation) and PT24_Scale_Data (full
 * 80-city × 10-service dataset).
 *
 * Tabs:
 *   Generator — quick generate / batch queue / CSV import
 *   Statystyki — factory stats, queue status, recent pages
 *   WP-CLI     — command reference
 *
 * @package PearBlog
 * @subpackage PT24
 * @version 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PearBlog_PT24_Landing_Admin {

	public static function init(): void {
		add_action( 'admin_menu', [ __CLASS__, 'add_admin_page' ] );
	}

	public static function add_admin_page(): void {
		add_submenu_page(
			'edit.php?post_type=pt24_landing',
			'PT24 — Generator i Statystyki',
			'⚙ Generator',
			'manage_options',
			'pt24-generator',
			[ __CLASS__, 'render_admin_page' ]
		);
	}

	/* =====================================================================
	   RENDER
	   ===================================================================== */

	public static function render_admin_page(): void {
		$factory_ok = class_exists( 'PT24_AI_Factory' );
		$scale_ok   = class_exists( 'PT24_Scale_Data' );

		$services = $scale_ok
			? array_map( fn( $d ) => $d['name'], PT24_Scale_Data::services() )
			: ( class_exists( 'PearBlog_PT24_Landing_CPT' ) ? PearBlog_PT24_Landing_CPT::get_services() : [] );

		$cities = $scale_ok
			? array_map( fn( $d ) => is_array( $d ) ? $d['name'] : $d, PT24_Scale_Data::cities() )
			: ( class_exists( 'PearBlog_PT24_Landing_CPT' ) ? PearBlog_PT24_Landing_CPT::get_cities() : [] );

		$stats  = $factory_ok ? PT24_AI_Factory::get_stats() : [];
		$nonce  = wp_create_nonce( 'pt24_factory_nonce' );

		$tab = isset( $_GET['pt24tab'] ) ? sanitize_key( $_GET['pt24tab'] ) : 'generator';

		$tab_url = fn( $t ) => esc_url( add_query_arg( [
			'post_type' => 'pt24_landing',
			'page'      => 'pt24-generator',
			'pt24tab'   => $t,
		], admin_url( 'edit.php' ) ) );
		?>
		<div class="wrap">
			<h1 style="display:flex;align-items:center;gap:10px;">
				PT24 — Generator landing pages
				<a href="<?php echo esc_url( add_query_arg( [ 'page' => 'pearblog-enterprise-v8', 'tab' => 'content' ], admin_url( 'admin.php' ) ) ); ?>"
				   style="font-size:13px;font-weight:400;text-decoration:none;color:#2563eb;">
					→ Pełny panel Enterprise V8
				</a>
			</h1>

			<!-- Tab navigation -->
			<nav class="nav-tab-wrapper" style="margin-bottom:20px;">
				<a href="<?php echo $tab_url('generator'); ?>" class="nav-tab<?php echo 'generator' === $tab ? ' nav-tab-active' : ''; ?>">⚙ Generator</a>
				<a href="<?php echo $tab_url('stats'); ?>"     class="nav-tab<?php echo 'stats'     === $tab ? ' nav-tab-active' : ''; ?>">📊 Statystyki</a>
				<a href="<?php echo $tab_url('cli'); ?>"       class="nav-tab<?php echo 'cli'       === $tab ? ' nav-tab-active' : ''; ?>">⌨ WP-CLI</a>
			</nav>

			<?php if ( 'generator' === $tab ) : ?>
			<!-- ──────────────────── GENERATOR ──────────────────── -->
			<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;max-width:1100px;">

				<!-- Quick generate -->
				<div class="card" style="padding:20px;">
					<h2 style="margin-top:0">🤖 Generuj jedną stronę (AI)</h2>
					<p style="color:#666;font-size:13px;">Wybierz usługę + miasto → OpenAI generuje gotową stronę lokalną.</p>
					<?php if ( ! $factory_ok ) : ?>
						<div class="notice notice-warning inline" style="margin:10px 0;"><p>PT24_AI_Factory nie znaleziona.</p></div>
					<?php else : ?>
					<table class="form-table" style="margin:0;">
						<tr>
							<th style="width:100px;">Usługa</th>
							<td>
								<select id="pt24svc" style="min-width:200px;padding:5px;">
									<?php foreach ( $services as $slug => $name ) : ?>
										<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $name ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th>Miasto</th>
							<td>
								<select id="pt24city" style="min-width:200px;padding:5px;">
									<?php foreach ( $cities as $slug => $name ) : ?>
										<option value="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $name ); ?></option>
									<?php endforeach; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th>Tryb</th>
							<td>
								<label><input type="radio" name="pt24mode" value="ai" <?php echo '' !== get_option( 'pt24_openai_api_key', '' ) ? 'checked' : ''; ?>> 🤖 AI (OpenAI)</label>
								&nbsp;&nbsp;
								<label><input type="radio" name="pt24mode" value="template" <?php echo '' === get_option( 'pt24_openai_api_key', '' ) ? 'checked' : ''; ?>> 📄 Szablon</label>
							</td>
						</tr>
					</table>
					<button class="button button-primary" style="margin-top:12px;" onclick="pt24gen.generateSingle('<?php echo esc_js( $nonce ); ?>')">
						Generuj stronę
					</button>
					<span id="pt24GenMsg" style="margin-left:10px;font-size:13px;"></span>
					<?php endif; ?>
				</div>

				<!-- Batch queue -->
				<div class="card" style="padding:20px;">
					<h2 style="margin-top:0">📦 Batch — kolejkuj masowo</h2>
					<p style="color:#666;font-size:13px;">Dodaje kombinacje do kolejki WP-Cron (<?php echo esc_html( $factory_ok ? PT24_AI_Factory::BATCH_SIZE : 5 ); ?> stron/minutę).</p>
					<table class="form-table" style="margin:0;">
						<tr>
							<th style="width:100px;">Tryb</th>
							<td>
								<label><input type="radio" name="pt24batchmode" value="ai" checked> 🤖 AI</label>
								&nbsp;
								<label><input type="radio" name="pt24batchmode" value="template"> 📄 Szablon</label>
							</td>
						</tr>
					</table>
					<div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap;">
						<button class="button button-primary" onclick="pt24gen.queueAll('<?php echo esc_js( $nonce ); ?>')">
							📦 Kolejkuj wszystkie (<?php echo count( $services ) * count( $cities ); ?> stron)
						</button>
						<button class="button" onclick="pt24gen.runQueue('<?php echo esc_js( $nonce ); ?>')">
							▶ Uruchom kolejkę
						</button>
						<?php if ( ( $stats['queue_size'] ?? 0 ) > 0 ) : ?>
						<button class="button" style="color:#b32d2e;" onclick="pt24gen.clearQueue('<?php echo esc_js( $nonce ); ?>')">
							🗑 Wyczyść kolejkę
						</button>
						<?php endif; ?>
					</div>
					<div id="pt24BatchMsg" style="margin-top:10px;font-size:13px;"></div>

					<?php if ( ( $stats['queue_size'] ?? 0 ) > 0 ) : ?>
					<div class="notice notice-info inline" style="margin-top:12px;">
						<p>⏳ W kolejce: <strong><?php echo number_format_i18n( $stats['queue_size'] ); ?></strong> stron — WP-Cron generuje automatycznie.</p>
					</div>
					<?php endif; ?>
				</div>

				<!-- CSV import -->
				<div class="card" style="padding:20px;">
					<h2 style="margin-top:0">📥 Import CSV</h2>
					<p style="color:#666;font-size:13px;">Format: <code>usluga,miasto</code> (jeden wiersz = jedna strona).</p>
					<textarea id="pt24csv" rows="6" style="width:100%;font-family:monospace;font-size:12px;padding:6px;" placeholder="hydraulik,krakow&#10;elektryk,warszawa&#10;mechanik,katowice"></textarea>
					<div style="margin-top:8px;display:flex;gap:8px;align-items:center;">
						<button class="button button-primary" onclick="pt24gen.importCsv('<?php echo esc_js( $nonce ); ?>')">📥 Kolejkuj z CSV</button>
						<label><input type="checkbox" id="pt24csvAI" checked> AI</label>
					</div>
					<div id="pt24CsvMsg" style="margin-top:8px;font-size:13px;"></div>
				</div>

				<!-- Stats snapshot -->
				<div class="card" style="padding:20px;">
					<h2 style="margin-top:0">📊 Statystyki (live)</h2>
					<table class="widefat striped" style="font-size:13px;">
						<tr><td>Opublikowane strony</td><td><strong><?php echo number_format_i18n( $stats['published'] ?? 0 ); ?></strong></td></tr>
						<tr><td>Wygenerowane przez AI</td><td><strong><?php echo number_format_i18n( $stats['ai_generated'] ?? 0 ); ?></strong></td></tr>
						<tr><td>Z szablonu (factory)</td><td><strong><?php echo number_format_i18n( $stats['factory_gen'] ?? 0 ); ?></strong></td></tr>
						<tr><td>W kolejce</td><td><strong><?php echo number_format_i18n( $stats['queue_size'] ?? 0 ); ?></strong></td></tr>
						<tr><td>Cel (usługi × miasta)</td><td><strong><?php echo number_format_i18n( $stats['target'] ?? 0 ); ?></strong></td></tr>
						<tr><td>Postęp</td><td><strong><?php echo number_format( $stats['progress_pct'] ?? 0, 1 ); ?>%</strong></td></tr>
						<tr><td>Pozostało</td><td><strong><?php echo number_format_i18n( $stats['remaining'] ?? 0 ); ?></strong></td></tr>
						<tr><td>Klucz OpenAI</td><td><?php echo ( $stats['has_api_key'] ?? false ) ? '<span style="color:green">✓ Ustawiony</span>' : '<span style="color:red">✗ Brak</span>'; ?></td></tr>
					</table>
					<p style="margin-top:12px;"><a href="<?php echo $tab_url('stats'); ?>" class="button">📋 Szczegółowe statystyki →</a></p>
				</div>

			</div><!-- grid -->

			<?php elseif ( 'stats' === $tab ) : ?>
			<!-- ──────────────────── STATYSTYKI ──────────────────── -->
			<div style="max-width:1100px;">
				<h2>Ostatnio wygenerowane strony</h2>
				<?php
				global $wpdb;
				$pages = $wpdb->get_results(
					"SELECT p.ID, p.post_title, p.post_date, p.post_name,
					        pm.meta_value  AS service,
					        pm2.meta_value AS city,
					        pm3.meta_value AS ai_flag,
					        pm4.meta_value AS variant
					 FROM {$wpdb->posts} p
					 LEFT JOIN {$wpdb->postmeta} pm  ON p.ID = pm.post_id  AND pm.meta_key  = 'pt24_service'
					 LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = 'pt24_city'
					 LEFT JOIN {$wpdb->postmeta} pm3 ON p.ID = pm3.post_id AND pm3.meta_key = '_pt24_ai_content'
					 LEFT JOIN {$wpdb->postmeta} pm4 ON p.ID = pm4.post_id AND pm4.meta_key = 'pt24_variant'
					 WHERE p.post_type = 'pt24_landing' AND p.post_status = 'publish'
					 ORDER BY p.post_date DESC LIMIT 50"
				);
				?>
				<table class="widefat striped" style="font-size:13px;">
					<thead><tr>
						<th>Tytuł</th><th>Usługa</th><th>Miasto</th><th>Wariant</th><th>AI</th><th>Data</th><th>URL</th>
					</tr></thead>
					<tbody>
					<?php foreach ( $pages as $pg ) :
						$url = home_url( '/' . $pg->city . '/' . $pg->service . '/' );
					?>
					<tr>
						<td><?php echo esc_html( $pg->post_title ); ?></td>
						<td><?php echo esc_html( $pg->service ?: '—' ); ?></td>
						<td><?php echo esc_html( $pg->city ?: '—' ); ?></td>
						<td><code>#<?php echo esc_html( $pg->variant ?? '?' ); ?></code></td>
						<td><?php echo $pg->ai_flag ? '<span style="color:green">🤖</span>' : '📄'; ?></td>
						<td><?php echo esc_html( mysql2date( 'd.m.Y', $pg->post_date ) ); ?></td>
						<td><a href="<?php echo esc_url( $url ); ?>" target="_blank">↗</a></td>
					</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<?php elseif ( 'cli' === $tab ) : ?>
			<!-- ──────────────────── WP-CLI ──────────────────── -->
			<div style="max-width:800px;">
				<h2>WP-CLI — Landing Pages</h2>
				<pre style="background:#f1f1f1;padding:16px;border-radius:6px;overflow-x:auto;font-size:13px;">
# Generuj strony (szablon)
wp pt24 generate
wp pt24 generate --services=hydraulik,elektryk --cities=krakow,warszawa

# Import CSV
wp pt24 import landings.csv

# Listuj
wp pt24 list

# Usuń wszystkie
wp pt24 delete-all

# Flush rewrites
wp pt24 flush-rewrites</pre>

				<h2 style="margin-top:20px;">WP-CLI — Blog Engine</h2>
				<pre style="background:#f1f1f1;padding:16px;border-radius:6px;overflow-x:auto;font-size:13px;">
# Generuj artykuł
wp pt24-blog generate "Pękła rura" --service=hydraulik --city=katowice

# 100 tematów startowych
wp pt24-blog queue-starters --city=katowice

# Import CSV (temat,usluga,miasto)
wp pt24-blog import-csv topics.csv

# Uruchom kolejkę (10 batchy × 5 artykułów)
wp pt24-blog run-queue --batches=10

# Statystyki
wp pt24-blog stats</pre>

				<h2 style="margin-top:20px;">WP-CLI — Google Places</h2>
				<pre style="background:#f1f1f1;padding:16px;border-radius:6px;overflow-x:auto;font-size:13px;">
# Seed firm dla pary
wp pt24-places seed --service=mechanik --city=katowice --ai

# Kolejkuj wszystkie kombinacje (800 par)
wp pt24-places queue-all --ai

# Uruchom kolejkę
wp pt24-places run-queue --batches=100

# Import CSV (places_seed)
wp pt24-places import-csv places_seed.csv --ai

# Statystyki
wp pt24-places stats</pre>
			</div>

			<?php endif; ?>

		</div><!-- .wrap -->

		<script>
		var pt24gen = {
			ajaxUrl: '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
			generateSingle: function(nonce) {
				var svc  = document.getElementById('pt24svc').value;
				var city = document.getElementById('pt24city').value;
				var mode = document.querySelector('input[name=pt24mode]:checked')?.value || 'template';
				var msg  = document.getElementById('pt24GenMsg');
				msg.style.color=''; msg.textContent = '⏳ Generuję…';
				var d = new FormData();
				d.append('action','pt24_factory_generate'); d.append('nonce',nonce);
				d.append('service',svc); d.append('city',city);
				d.append('use_ai', mode === 'ai' ? '1' : '');
				fetch(this.ajaxUrl,{method:'POST',body:d}).then(r=>r.json()).then(r=>{
					msg.style.color = r.success?'green':'red';
					if(r.success){
						msg.innerHTML='✅ '+r.data.message+' — <a href="'+r.data.url+'" target="_blank">Podgląd</a>';
					} else {
						msg.textContent='❌ '+(r.data?.message||'Błąd');
					}
				}).catch(()=>{ msg.style.color='red'; msg.textContent='❌ Błąd połączenia'; });
			},
			queueAll: function(nonce) {
				var mode = document.querySelector('input[name=pt24batchmode]:checked')?.value || 'ai';
				var msg  = document.getElementById('pt24BatchMsg');
				msg.style.color=''; msg.textContent='⏳ Kolejkuję wszystko…';
				var d = new FormData();
				d.append('action','pt24_factory_batch_csv'); d.append('nonce',nonce);
				d.append('use_ai', mode === 'ai' ? '1' : '');
				fetch(this.ajaxUrl,{method:'POST',body:d}).then(r=>r.json()).then(r=>{
					msg.style.color=r.success?'green':'red';
					msg.textContent=r.success?'✅ '+r.data.message:'❌ '+(r.data?.message||'Błąd');
				}).catch(()=>{ msg.style.color='red'; msg.textContent='❌ Błąd połączenia'; });
			},
			runQueue: function(nonce) {
				var msg = document.getElementById('pt24BatchMsg');
				msg.style.color=''; msg.textContent='⏳ Generuję partię…';
				var d = new FormData();
				d.append('action','pt24_factory_run_queue'); d.append('nonce',nonce);
				fetch(this.ajaxUrl,{method:'POST',body:d}).then(r=>r.json()).then(r=>{
					msg.style.color=r.success?'green':'red';
					msg.textContent=r.success?'✅ '+r.data.message:'❌ '+(r.data?.message||'Błąd');
					if(r.success) setTimeout(()=>location.reload(),1500);
				}).catch(()=>{ msg.style.color='red'; msg.textContent='❌ Błąd połączenia'; });
			},
			clearQueue: function(nonce) {
				if(!confirm('Wyczyścić kolejkę landing pages?')) return;
				var msg = document.getElementById('pt24BatchMsg');
				var d = new FormData();
				d.append('action','pt24_factory_clear_queue'); d.append('nonce',nonce);
				fetch(this.ajaxUrl,{method:'POST',body:d}).then(r=>r.json()).then(r=>{
					msg.style.color=r.success?'green':'red';
					msg.textContent=r.success?'✅ '+r.data.message:'❌ '+(r.data?.message||'Błąd');
					if(r.success) setTimeout(()=>location.reload(),1000);
				});
			},
			importCsv: function(nonce) {
				var csv  = document.getElementById('pt24csv').value.trim();
				var ai   = document.getElementById('pt24csvAI').checked;
				var msg  = document.getElementById('pt24CsvMsg');
				if(!csv){ msg.textContent='⚠️ Podaj dane CSV.'; return; }
				msg.style.color=''; msg.textContent='⏳ Przetwarzam CSV…';
				var d = new FormData();
				d.append('action','pt24_factory_batch_csv'); d.append('nonce',nonce);
				d.append('csv',csv); d.append('use_ai', ai ? '1' : '');
				fetch(this.ajaxUrl,{method:'POST',body:d}).then(r=>r.json()).then(r=>{
					msg.style.color=r.success?'green':'red';
					msg.textContent=r.success?'✅ '+r.data.message:'❌ '+(r.data?.message||'Błąd');
				}).catch(()=>{ msg.style.color='red'; msg.textContent='❌ Błąd połączenia'; });
			}
		};
		</script>
		<?php
	}
}

add_action( 'admin_menu', [ 'PearBlog_PT24_Landing_Admin', 'add_admin_page' ] );
