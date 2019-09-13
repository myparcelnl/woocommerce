<?php if ( ! defined('ABSPATH')) exit; // Exit if accessed directly ?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <?php
            wp_enqueue_script(
                'wcmyparcelbe-export',
                WCMP()->plugin_url() . '/assets/js/wcmp-admin.js',
                array( 'jquery', 'thickbox' ),
                WC_MYPARCEL_BE_VERSION
            );
            wp_localize_script(
                'wcmyparcelbe-export',
                'wc_myparcelbe',
                array(
                    'ajax_url'         => admin_url('admin-ajax.php'),
                    'nonce'            => wp_create_nonce('wc_myparcelbe'),
                    WCMP_Settings::SETTING_DOWNLOAD_DISPLAY => WCMP()->setting_collection->getByName(WCMP_Settings::SETTING_DOWNLOAD_DISPLAY)
                        ? WCMP()->setting_collection->getByName(WCMP_Settings::SETTING_DOWNLOAD_DISPLAY)
                        : '',
                )
            );

            wp_enqueue_style(
                'wcmp-admin-styles',
                WCMP()->plugin_url() . '/assets/css/wcmp-admin-styles.css',
                array(),
                WC_MYPARCEL_BE_VERSION,
                'all'
            );

            // Legacy styles (WC 2.1+ introduced MP6 style with larger buttons)
            if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '<=' ) ) {
                wp_enqueue_style(
                    'wcmp-admin-styles-legacy',
                    WCMP()->plugin_url() . '/assets/css/wcmp-admin-styles-legacy.css',
                    array(),
                    WC_MYPARCEL_BE_VERSION,
                    'all'
                );
            }

            wp_enqueue_style( 'wcmyparcelbe-admin-styles' );
            wp_enqueue_style( 'colors' );
            wp_enqueue_style( 'media' );
            wp_enqueue_script( 'jquery' );
            do_action('admin_print_styles');
            do_action('admin_print_scripts');
        ?>

    </head>
    <body style="padding:10px 20px;">
        <?php
            switch ($request) {
                case 'add_return':
                    printf('<h3>%s</h3>', __('Return email successfully sent to customer'));
                    break;
        }
        ?>
    </body>
</html>
