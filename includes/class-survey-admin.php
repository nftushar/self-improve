<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyAdmin {
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_admin_pages' ] );
        add_action( 'admin_post_create_survey', [ $this, 'handle_create_survey' ] );
        add_action( 'admin_post_delete_survey', [ $this, 'handle_delete_survey' ] );
    }

    public function register_admin_pages() {
        add_submenu_page(
            'tools.php',
            'Dynamic Survey',
            'Dynamic Survey',
            'manage_options',
            'dynamic-survey',
            [ $this, 'render_admin_page' ]
        );
    }

    public function render_admin_page() {
        global $wpdb;
        $surveys_table = $wpdb->prefix . 'dynamic_surveys';
        $surveys = $wpdb->get_results( "SELECT * FROM $surveys_table" );

        require_once DYNAMIC_SURVEY_PATH . 'templates/admin-survey-page.php';
    }

    public function handle_create_survey() {
        if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'create_survey_nonce' ) ) {
            wp_die( 'Unauthorized action!' );
        }

        global $wpdb;
        $surveys_table = $wpdb->prefix . 'dynamic_surveys';
        $options_table = $wpdb->prefix . 'dynamic_survey_options';

        $question = sanitize_text_field( $_POST['survey_question'] );
        $options = array_map( 'sanitize_text_field', $_POST['survey_options'] );

        $wpdb->insert( $surveys_table, [ 'question' => $question ] );
        $survey_id = $wpdb->insert_id;

        foreach ( $options as $option ) {
            $wpdb->insert( $options_table, [
                'survey_id'   => $survey_id,
                'option_text' => $option,
            ]);
        }

        wp_redirect( admin_url( 'tools.php?page=dynamic-survey&status=created' ) );
        exit;
    }

    public function handle_delete_survey() {
        if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'delete_survey_nonce' ) ) {
            wp_die( 'Unauthorized action!' );
        }

        global $wpdb;
        $surveys_table = $wpdb->prefix . 'dynamic_surveys';

        $survey_id = intval( $_GET['survey_id'] );
        $wpdb->delete( $surveys_table, [ 'id' => $survey_id ] );

        wp_redirect( admin_url( 'tools.php?page=dynamic-survey&status=deleted' ) );
        exit;
    }
}
