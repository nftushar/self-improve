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
        if ( ! isset( $_POST['survey_id'], $_POST['option_id'] ) || ! is_user_logged_in() ) {
            wp_send_json_error( 'Invalid request or not logged in.' );
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
        ]);

        if ( $inserted ) {
            wp_send_json_success( 'Your vote has been recorded successfully!' );
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
}

new SurveyVoting();
