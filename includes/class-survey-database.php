<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SurveyDatabase {
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Table for storing surveys
        $table_name_surveys = $wpdb->prefix . 'dynamic_surveys';
        $sql = "
            CREATE TABLE $table_name_surveys (
                id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                question VARCHAR(255) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) $charset_collate;
        ";
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );

        // Table for storing survey options
        $table_name_options = $wpdb->prefix . 'dynamic_survey_options';
        $sql = "
            CREATE TABLE $table_name_options (
                id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                survey_id BIGINT(20) UNSIGNED NOT NULL,
                option_text VARCHAR(255) NOT NULL,
                votes INT DEFAULT 0,
                FOREIGN KEY (survey_id) REFERENCES $table_name_surveys(id) ON DELETE CASCADE
            ) $charset_collate;
        ";
        dbDelta( $sql );

        // Table for storing votes
        $table_name_votes = $wpdb->prefix . 'dynamic_survey_votes';
        $sql = "
            CREATE TABLE $table_name_votes (
                id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                survey_id BIGINT(20) UNSIGNED NOT NULL,
                option_id BIGINT(20) UNSIGNED NOT NULL,
                user_id BIGINT(20) UNSIGNED NOT NULL,
                user_ip VARCHAR(100),
                vote_time DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_vote (survey_id, user_id),
                FOREIGN KEY (survey_id) REFERENCES $table_name_surveys(id) ON DELETE CASCADE,
                FOREIGN KEY (option_id) REFERENCES $table_name_options(id) ON DELETE CASCADE
            ) $charset_collate;
        ";
        dbDelta( $sql );
    }
}

// Hook to ensure tables are created during plugin activation
register_activation_hook( __FILE__, [ 'SurveyDatabase', 'create_tables' ] );
