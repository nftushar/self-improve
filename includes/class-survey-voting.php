<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyVoting {

    public function __construct() {
        add_action( 'wp_ajax_submit_survey_vote', [ $this, 'handle_survey_vote' ] );
        add_action( 'wp_ajax_nopriv_submit_survey_vote', [ $this, 'restrict_non_logged_in' ] );
    }

    /**
     * Handle survey vote submission by logged-in users.
     */
    public function handle_survey_vote() {
        if ( ! isset( $_POST['survey_id'], $_POST['option_id'], $_POST['nonce'] ) || ! is_user_logged_in() ) {
            wp_send_json_error( 'Invalid request or not logged in.' );
        }

        if ( ! wp_verify_nonce( $_POST['nonce'], 'submit_survey_vote' ) ) {
            wp_send_json_error( 'Invalid nonce.' );
        }

        global $wpdb;

        $survey_id = intval( $_POST['survey_id'] );
        $option_id = intval( $_POST['option_id'] );
        $user_id   = get_current_user_id();

        $votes_table = $wpdb->prefix . 'dynamic_survey_votes';

        // Check if the user has already voted for this survey
        $already_voted = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $votes_table WHERE survey_id = %d AND user_id = %d",
            $survey_id, $user_id
        ));

        if ( $already_voted > 0 ) {
            wp_send_json_error( 'You have already voted in this survey.' );
        }

        // Insert the user's vote
        $inserted = $wpdb->insert( $votes_table, [
            'survey_id' => $survey_id,
            'option_id' => $option_id,
            'user_id'   => $user_id,
            'vote_time' => current_time( 'mysql' )
        ]);

        if ( $inserted ) {
            // Fetch survey results for the response
            $results = $wpdb->get_results( $wpdb->prepare(
                "SELECT option_id, COUNT(*) AS votes FROM $votes_table WHERE survey_id = %d GROUP BY option_id",
                $survey_id
            ));

            $labels = [];
            $data   = [];
            foreach ( $results as $result ) {
                $labels[] = 'Option ' . $result->option_id;
                $data[]   = (int) $result->votes;
            }

            wp_send_json_success( [
                'message'     => 'Your vote has been recorded successfully!',
                'results_html'=> $this->generate_results_html( $survey_id ),
                'chart_data'  => [
                    'labels' => $labels,
                    'data'   => $data
                ]
            ]);
        } else {
            wp_send_json_error( 'Failed to record your vote. Please try again.' );
        }
    }

    /**
     * Restrict non-logged-in users from voting.
     */
    public function restrict_non_logged_in() {
        wp_send_json_error( 'You must be logged in to vote.' );
    }

    /**
     * Generate HTML for the results chart.
     */
    private function generate_results_html( $survey_id ) {
        return '<canvas id="survey-chart-' . esc_attr( $survey_id ) . '"></canvas>';
    }

    /**
     * Get all votes for admin tracking.
     */
    public static function get_all_votes() {
        global $wpdb;
        $votes_table = $wpdb->prefix . 'dynamic_survey_votes';

        return $wpdb->get_results( "
            SELECT survey_id, option_id, user_id, vote_time
            FROM $votes_table
            ORDER BY vote_time DESC
        ", ARRAY_A );
    }
}

new SurveyVoting();