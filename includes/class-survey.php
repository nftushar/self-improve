<?php

add_action( 'wp_enqueue_scripts', function() {
    // Ensure DYNAMIC_SURVEY_URL is defined correctly
    if ( ! defined( 'DYNAMIC_SURVEY_URL' ) ) {
        define( 'DYNAMIC_SURVEY_URL', plugin_dir_url( __FILE__ ) ); // Use this to define the plugin URL
    }

    // Enqueue Chart.js and your custom JS for survey
    wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '3.8.0', true );
    wp_enqueue_script( 'dynamic-survey-js', DYNAMIC_SURVEY_URL . 'js/survey-frontend.js', [ 'jquery', 'chart-js' ], null, true );

    // Localize script to handle AJAX URL and nonce
    wp_localize_script( 'dynamic-survey-js', 'DynamicSurvey', [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'dynamic_survey_vote_nonce' ),
    ]);
});
