<?php defined('ABSPATH') or die(); ?>

<!DOCTYPE html>
<html
    xmlns="http://www.w3.org/1999/xhtml" lang="<?php get_locale(); ?>">
<head>
    <meta
        http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title></title>
    <?php
    WCMPBE_Assets::enqueue_admin_scripts_and_styles();
    wp_enqueue_style('colors');
    wp_enqueue_style('media');
    wp_enqueue_script('jquery');

    do_action('admin_print_styles');
    do_action('admin_print_scripts');
    ?>
</head>
<body class="wp-core-ui">
