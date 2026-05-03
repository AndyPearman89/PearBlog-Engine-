<?php
/**
 * Template Part: AI Panel V2 Pro
 *
 * Interactive AI input panel for lead capture
 *
 * @package PearBlog
 * @version 2.0.0
 */

$args = wp_parse_args($args ?? array(), array(
    'title' => 'Jak mogę Ci pomóc?',
    'placeholder' => 'Napisz swój problem...',
    'cta_primary' => 'Generuj odpowiedź',
    'cta_secondary' => 'Przejdź do eksperta',
));
?>

<section class="v2pro-section">
    <div class="v2pro-container">
        <div class="v2pro-ai-panel v2pro-fade-in">
            <h2 class="v2pro-ai-title v2pro-text-center v2pro-gradient-text">
                <?php echo esc_html($args['title']); ?>
            </h2>

            <form id="v2pro-ai-form" data-autosave="ai-input" class="v2pro-ai-form">
                <div class="v2pro-ai-input-wrapper">
                    <textarea
                        name="problem"
                        class="v2pro-ai-input"
                        placeholder="<?php echo esc_attr($args['placeholder']); ?>"
                        rows="4"
                        required
                    ></textarea>
                </div>

                <div class="v2pro-ai-actions">
                    <button
                        type="submit"
                        class="v2pro-btn"
                        data-cta-id="ai-generate"
                        data-cta-location="ai-panel"
                    >
                        → <?php echo esc_html($args['cta_primary']); ?>
                    </button>

                    <a
                        href="<?php echo esc_url(home_url('/eksperci')); ?>"
                        class="v2pro-btn v2pro-btn-secondary"
                        data-cta-id="ai-expert"
                        data-cta-location="ai-panel"
                    >
                        → <?php echo esc_html($args['cta_secondary']); ?>
                    </a>
                </div>
            </form>

            <!-- AI Response Container (hidden by default) -->
            <div id="v2pro-ai-response" class="v2pro-ai-response v2pro-hidden">
                <!-- Populated via JavaScript -->
            </div>
        </div>
    </div>
</section>

<style>
.v2pro-ai-response {
    margin-top: var(--v2pro-space-lg);
    padding: var(--v2pro-space-lg);
    background: var(--v2pro-glass-bg);
    border: 1px solid var(--v2pro-glass-border);
    border-radius: var(--v2pro-radius);
    backdrop-filter: blur(var(--v2pro-glass-blur));
}

.v2pro-ai-response.v2pro-hidden {
    display: none;
}

.v2pro-ai-response.v2pro-loading {
    text-align: center;
    color: var(--v2pro-text-muted);
}

.v2pro-ai-response.v2pro-loading::before {
    content: '';
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 2px solid var(--v2pro-pink);
    border-top-color: transparent;
    border-radius: 50%;
    animation: v2pro-spin 0.8s linear infinite;
    margin-right: var(--v2pro-space-sm);
}

@keyframes v2pro-spin {
    to { transform: rotate(360deg); }
}
</style>

<script>
(function() {
    'use strict';

    const form = document.getElementById('v2pro-ai-form');
    const responseContainer = document.getElementById('v2pro-ai-response');

    if (!form || !responseContainer) return;

    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(form);
        const problem = formData.get('problem');

        if (!problem || problem.length < 10) {
            alert('Proszę opisać problem bardziej szczegółowo (minimum 10 znaków).');
            return;
        }

        // Show loading state
        responseContainer.classList.remove('v2pro-hidden');
        responseContainer.classList.add('v2pro-loading');
        responseContainer.innerHTML = 'Analizuję Twój problem...';

        try {
            // Send to backend
            const response = await fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'v2pro_ai_analyze',
                    problem: problem,
                    nonce: '<?php echo wp_create_nonce('v2pro_ai_nonce'); ?>'
                })
            });

            if (!response.ok) {
                throw new Error('Network response was not ok');
            }

            const data = await response.json();

            // Show response
            responseContainer.classList.remove('v2pro-loading');

            if (data.success) {
                responseContainer.innerHTML = `
                    <h3 style="margin-bottom: var(--v2pro-space-md); color: var(--v2pro-text);">
                        Rekomendacja:
                    </h3>
                    <p style="color: var(--v2pro-text-muted); line-height: 1.6; margin-bottom: var(--v2pro-space-md);">
                        ${data.data.response || 'Znaleziono dopasowane rozwiązanie.'}
                    </p>
                    <a href="${data.data.url || '/eksperci'}" class="v2pro-btn">
                        Zobacz szczegóły
                    </a>
                `;
            } else {
                responseContainer.innerHTML = `
                    <p style="color: var(--v2pro-text-muted);">
                        Przepraszamy, wystąpił problem. Skontaktuj się z ekspertem bezpośrednio.
                    </p>
                    <a href="/eksperci" class="v2pro-btn" style="margin-top: var(--v2pro-space-md);">
                        Znajdź eksperta
                    </a>
                `;
            }
        } catch (error) {
            console.error('AI Analysis Error:', error);

            responseContainer.classList.remove('v2pro-loading');
            responseContainer.innerHTML = `
                <p style="color: var(--v2pro-text-muted);">
                    Przepraszamy, wystąpił błąd. Spróbuj ponownie lub skontaktuj się z ekspertem.
                </p>
                <a href="/eksperci" class="v2pro-btn" style="margin-top: var(--v2pro-space-md);">
                    Znajdź eksperta
                </a>
            `;
        }
    });
})();
</script>
