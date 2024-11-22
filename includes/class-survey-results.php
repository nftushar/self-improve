<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyResults {

    public function __construct() {
        add_action( 'wp_ajax_get_survey_results', [ $this, 'get_survey_results' ] );
        add_action( 'wp_ajax_nopriv_get_survey_results', [ $this, 'restrict_non_logged_in' ] );
    }

    /**
     * Get aggregated survey results (vote counts) for a specific survey.
     */
    public function get_survey_results() {
        if ( ! isset( $_POST['survey_id'] ) ) {
            wp_send_json_error( 'Invalid request.' );
        }

        global $wpdb;

        $survey_id = intval( $_POST['survey_id'] );
        $votes_table = $wpdb->prefix . 'dynamic_survey_votes';
        $options_table = $wpdb->prefix . 'dynamic_survey_options';

        // Get the survey options and vote counts
        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT o.option_id, o.option_text, COUNT(v.option_id) AS votes
            FROM $options_table o
            LEFT JOIN $votes_table v ON o.option_id = v.option_id AND v.survey_id = %d
            GROUP BY o.option_id",
            $survey_id
        ) );

        // Prepare chart data
        $labels = [];
        $data = [];
        foreach ( $results as $result ) {
            $labels[] = $result->option_text;
            $data[] = $result->votes;
        }

        // Return results in JSON format
        wp_send_json_success( [
            'labels' => $labels,
            'data'   => $data
        ] );
    }

    /**
     * Restrict non-logged-in users from requesting results.
     */
    public function restrict_non_logged_in() {
        wp_send_json_error( 'You must be logged in to view results.' );
    }
}

new SurveyResults();
