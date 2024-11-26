<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SurveyDatabase {
    public static function create_tables() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'dynamic_surveys'; // Table for storing surveys
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "
            CREATE TABLE $table_name (
                id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                question VARCHAR(255) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;
        ";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );

        // Create table for survey options
        $table_name_options = $wpdb->prefix . 'dynamic_survey_options'; // Table for storing options
        $sql = "
            CREATE TABLE $table_name_options (
                id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                survey_id BIGINT(20) UNSIGNED NOT NULL,
                option_text VARCHAR(255) NOT NULL,
                votes INT DEFAULT 0,
                FOREIGN KEY (survey_id) REFERENCES $table_name(id) ON DELETE CASCADE
            ) $charset_collate;
        ";
        dbDelta( $sql );

        // Create table for survey votes
        $table_name_votes = $wpdb->prefix . 'dynamic_survey_votes'; // Table for storing votes
        $sql = "
            CREATE TABLE $table_name_votes (
                id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                survey_id BIGINT(20) UNSIGNED NOT NULL,
                option_id BIGINT(20) UNSIGNED NOT NULL,
                user_ip VARCHAR(100),
                vote_time DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (survey_id) REFERENCES $table_name(id) ON DELETE CASCADE,
                FOREIGN KEY (option_id) REFERENCES $table_name_options(id) ON DELETE CASCADE
            ) $charset_collate;
        ";
        dbDelta( $sql );
    }
}

