<!doctype html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="[https://gmpg.org/xfn/11](https://gmpg.org/xfn/11)">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>

    <div id="page" class="site">
        <a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e('Skip to content', 'rigid-hybrid'); ?></a>

        <header id="masthead" class="site-header bg-white shadow-sm py-4">
            <div class="container mx-auto px-4 flex justify-between items-center">
                <div class="site-branding">
                    <?php
                    if (has_custom_logo()) {
                        the_custom_logo();
                    } else {
                    ?>
                        <h1 class="site-title text-xl font-bold"><a href="<?php echo esc_url(home_url('/')); ?>" rel="home"><?php bloginfo('name'); ?></a></h1>
                    <?php
                    }
                    ?>
                </div>

                <nav id="site-navigation" class="main-navigation">
                    <?php
                    wp_nav_menu(array(
                        'theme_location' => 'primary_menu',
                        'menu_id'        => 'primary-menu',
                        'container_class' => 'flex gap-4'
                    ));
                    ?>
                </nav>
            </div>
        </header>