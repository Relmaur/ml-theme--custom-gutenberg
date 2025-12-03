<?php get_header(); ?>

<main id="primary" class="site-main">
    <div class="container mx-auto px-4 py-8">
        <?php if (have_posts()) : ?>

            <?php while (have_posts()) : the_post(); ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class('mb-12'); ?>>

                    <header class="entry-header mb-4">
                        <?php the_title('<h1 class="entry-title text-3xl font-bold">', '</h1>'); ?>
                    </header>

                    <div class="entry-content prose max-w-none">
                        <?php the_content(); ?>
                    </div>

                </article>
            <?php endwhile; ?>

            <div class="pagination">
                <?php the_posts_navigation(); ?>
            </div>

        <?php else : ?>
            <p><?php esc_html_e('Nothing found.', 'rigid-hybrid'); ?></p>
        <?php endif; ?>
    </div>
</main>

<?php get_footer(); ?>