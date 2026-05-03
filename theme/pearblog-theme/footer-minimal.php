<!-- Minimal Footer for Landing Pages -->
<footer class="plv5-minimal-footer" style="
    background: #0a0e1a;
    color: rgba(255, 255, 255, 0.8);
    padding: 3rem 1rem 2rem;
">
    <div class="pb-container" style="
        max-width: 1200px;
        margin: 0 auto;
    ">
        <!-- Footer Grid -->
        <div style="
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        ">
            <!-- Company Info -->
            <div>
                <h3 style="
                    font-size: 1.25rem;
                    font-weight: 700;
                    margin: 0 0 1rem;
                    color: white;
                ">
                    <?php bloginfo('name'); ?>
                </h3>
                <p style="
                    font-size: 0.875rem;
                    line-height: 1.6;
                    margin: 0 0 1rem;
                ">
                    Łączymy klientów z najlepszymi wykonawcami. Szybko, bezpiecznie i za darmo.
                </p>
            </div>

            <!-- Quick Links -->
            <div>
                <h4 style="
                    font-size: 1rem;
                    font-weight: 600;
                    margin: 0 0 1rem;
                    color: white;
                ">
                    Szybkie linki
                </h4>
                <ul style="
                    list-style: none;
                    padding: 0;
                    margin: 0;
                ">
                    <li style="margin-bottom: 0.5rem;">
                        <a href="<?php echo esc_url(home_url('/o-nas/')); ?>" style="
                            color: rgba(255, 255, 255, 0.8);
                            text-decoration: none;
                            font-size: 0.875rem;
                        ">
                            O nas
                        </a>
                    </li>
                    <li style="margin-bottom: 0.5rem;">
                        <a href="<?php echo esc_url(home_url('/jak-to-dziala/')); ?>" style="
                            color: rgba(255, 255, 255, 0.8);
                            text-decoration: none;
                            font-size: 0.875rem;
                        ">
                            Jak to działa?
                        </a>
                    </li>
                    <li style="margin-bottom: 0.5rem;">
                        <a href="<?php echo esc_url(home_url('/kontakt/')); ?>" style="
                            color: rgba(255, 255, 255, 0.8);
                            text-decoration: none;
                            font-size: 0.875rem;
                        ">
                            Kontakt
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Legal -->
            <div>
                <h4 style="
                    font-size: 1rem;
                    font-weight: 600;
                    margin: 0 0 1rem;
                    color: white;
                ">
                    Informacje prawne
                </h4>
                <ul style="
                    list-style: none;
                    padding: 0;
                    margin: 0;
                ">
                    <li style="margin-bottom: 0.5rem;">
                        <a href="<?php echo esc_url(home_url('/polityka-prywatnosci/')); ?>" style="
                            color: rgba(255, 255, 255, 0.8);
                            text-decoration: none;
                            font-size: 0.875rem;
                        ">
                            Polityka prywatności
                        </a>
                    </li>
                    <li style="margin-bottom: 0.5rem;">
                        <a href="<?php echo esc_url(home_url('/regulamin/')); ?>" style="
                            color: rgba(255, 255, 255, 0.8);
                            text-decoration: none;
                            font-size: 0.875rem;
                        ">
                            Regulamin
                        </a>
                    </li>
                    <li style="margin-bottom: 0.5rem;">
                        <a href="<?php echo esc_url(home_url('/polityka-cookies/')); ?>" style="
                            color: rgba(255, 255, 255, 0.8);
                            text-decoration: none;
                            font-size: 0.875rem;
                        ">
                            Polityka cookies
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Contact -->
            <div>
                <h4 style="
                    font-size: 1rem;
                    font-weight: 600;
                    margin: 0 0 1rem;
                    color: white;
                ">
                    Kontakt
                </h4>
                <ul style="
                    list-style: none;
                    padding: 0;
                    margin: 0;
                ">
                    <li style="
                        margin-bottom: 0.5rem;
                        font-size: 0.875rem;
                    ">
                        📧 kontakt@poradnik.pro
                    </li>
                    <li style="
                        margin-bottom: 0.5rem;
                        font-size: 0.875rem;
                    ">
                        📞 +48 123 456 789
                    </li>
                    <li style="
                        margin-bottom: 0.5rem;
                        font-size: 0.875rem;
                    ">
                        🏢 Warszawa, Polska
                    </li>
                </ul>
            </div>
        </div>

        <!-- Bottom Bar -->
        <div style="
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        ">
            <div style="font-size: 0.875rem;">
                &copy; <?php echo date('Y'); ?> <?php bloginfo('name'); ?>. Wszelkie prawa zastrzeżone.
            </div>

            <div style="
                display: flex;
                gap: 1rem;
                font-size: 0.875rem;
            ">
                <a href="https://facebook.com" target="_blank" rel="noopener" style="
                    color: rgba(255, 255, 255, 0.8);
                    text-decoration: none;
                ">
                    Facebook
                </a>
                <a href="https://twitter.com" target="_blank" rel="noopener" style="
                    color: rgba(255, 255, 255, 0.8);
                    text-decoration: none;
                ">
                    Twitter
                </a>
                <a href="https://linkedin.com" target="_blank" rel="noopener" style="
                    color: rgba(255, 255, 255, 0.8);
                    text-decoration: none;
                ">
                    LinkedIn
                </a>
            </div>
        </div>
    </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
