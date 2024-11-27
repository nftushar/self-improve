<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyAdmin {
    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_admin_pages' ] );
        add_action( 'admin_post_create_survey', [ $this, 'handle_create_survey' ] );
        add_action( 'admin_post_delete_survey', [ $this, 'handle_delete_survey' ] );
    }
  
    public function register_admin_pages() {
        // Main menu: Dynamic Survey
        add_submenu_page(
            'tools.php',
            'Dynamic Survey',
            'Dynamic Survey',
            'manage_options',
            'dynamic-survey',
            [ $this, 'render_admin_page' ]
        );
  
        // Submenu: Survey Tracking
        add_submenu_page(
            'tools.php',
            'Survey Tracking',
            'Survey Tracking',
            'manage_options',
            'survey-tracking',
            [ $this, 'render_tracking_page' ]
        );
    }

    public function render_admin_page() {
        global $wpdb;
    
        // Define the surveys table
        $surveys_table = $wpdb->prefix . 'dynamic_surveys';
    
        // Check if the table exists
        if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $surveys_table ) ) === $surveys_table ) {
            // Fetch surveys data
            $surveys = $wpdb->get_results( "SELECT * FROM $surveys_table", ARRAY_A );
    
            // Include the admin survey page template
            require_once DYNAMIC_SURVEY_PATH . 'templates/admin-survey-page.php';
        } else {
            // Display an error message if the table doesn't exist
            echo '<div class="notice notice-error"><p>' . esc_html__( 'Dynamic Surveys table not found.', 'text-domain' ) . '</p></div>';
        }
    }
    
    public function render_tracking_page() {
        global $wpdb;

        $votes_table = $wpdb->prefix . 'dynamic_survey_votes';
        $surveys_table = $wpdb->prefix . 'dynamic_surveys';
        $options_table = $wpdb->prefix . 'dynamic_survey_options';

        $votes = $wpdb->get_results(
            "SELECT 
                v.survey_id, 
                s.question, 
                o.option_text, 
                COUNT(v.option_id) AS vote_count
             FROM $votes_table v
             JOIN $surveys_table s ON v.survey_id = s.id
             JOIN $options_table o ON v.option_id = o.id
             GROUP BY v.survey_id, v.option_id"
        );

        require_once DYNAMIC_SURVEY_PATH . 'templates/admin-tracking-page.php';
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
 