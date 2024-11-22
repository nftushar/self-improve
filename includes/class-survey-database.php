<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SurveyDatabase {
    public static function create_tables() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'dynamic_survey_votes';
        $charset_collate = $wpdb->get_charset_collate();

        // Debugging table creation
        error_log("Creating database table for survey votes");

        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            survey_id BIGINT(20) UNSIGNED NOT NULL,
            option_id BIGINT(20) UNSIGNED NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            vote_time DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );

        error_log("Survey database table created (or already exists).");
    }
    
    public static function insert_vote($survey_id, $option_id, $user_id) {
        global $wpdb;
        
        // Debugging data insert
        error_log("Inserting vote into database for Survey ID: $survey_id, Option ID: $option_id, User ID: $user_id");

        $table = $wpdb->prefix . 'dynamic_survey_votes';
        
        $result = $wpdb->insert( $table, [
            'survey_id' => $survey_id,
            'option_id' => $option_id,
            'user_id' => $user_id,
            'vote_time' => current_time( 'mysql' )
        ]);

        if ($result === false) {
            error_log("Error inserting vote: " . $wpdb->last_error);
            return false;
        }

        error_log("Vote inserted successfully.");
        return true;
    }

    public static function get_survey_results($survey_id) {
        global $wpdb;

        // Debugging survey result retrieval
        error_log("Retrieving results for Survey ID: $survey_id");

        $table = $wpdb->prefix . 'dynamic_survey_votes';
        $results = $wpdb->get_results(
            $wpdb->prepare("SELECT option_id, COUNT(*) as vote_count FROM $table WHERE survey_id = %d GROUP BY option_id", $survey_id)
        );

        if ($wpdb->last_error) {
            error_log("Error retrieving survey results: " . $wpdb->last_error);
            return [];
        }

        error_log("Survey results retrieved successfully.");
        return $results;
    }
}
