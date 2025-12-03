<footer id="colophon" class="site-footer bg-gray-900 text-white mt-12 py-8">
    <div class="container mx-auto px-4">
        <div class="site-info text-center">
            <a href="<?php echo esc_url(__('[https://wordpress.org/](https://wordpress.org/)', 'rigid-hybrid')); ?>">
                <?php
                /* translators: %s: CMS Name, i.e. WordPress. */
                printf(esc_html__('Proudly powered by %s', 'rigid-hybrid'), 'WordPress');
                ?>
            </a>
            <span class="sep"> | </span>
            <?php
            /* translators: 1: Theme name, 2: Theme author. */
            printf(esc_html__('Theme: %1$s by %2$s.', 'rigid-hybrid'), 'Rigid Hybrid', 'Your Name');
            ?>
        </div>

        <nav class="footer-navigation mt-4 text-center">
            <?php
            wp_nav_menu(array(
                'theme_location' => 'footer_menu',
                'depth'          => 1,
            ));
            ?>
        </nav>
    </div>
</footer>
</div><!-- #page -->

<?php wp_footer(); ?>

</body>

</html>