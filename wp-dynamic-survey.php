<?php
/**
 * Plugin Name: Dynamic Survey
 * Description: A plugin to create dynamic surveys and display results with charts.
 * Version: 1.0.2
 * Author: Your Name
 * Text Domain: dynamic-survey
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Main class to initialize the plugin
class DynamicSurvey {
    public function __construct() {
        // Define plugin constants
        $this->define_constants();

        // Include necessary files
        $this->include_files();

        // Initialize plugin classes
        $this->initialize_classes();

        // Hook to activation and deactivation
        register_activation_hook( __FILE__, [ $this, 'activate' ] );
        register_deactivation_hook( __FILE__, [ $this, 'deactivate' ] );
    }

    private function define_constants() {
        define( 'DYNAMIC_SURVEY_VERSION', '1.0.0' );
        define( 'DYNAMIC_SURVEY_PATH', plugin_dir_path( __FILE__ ) );
        define( 'DYNAMIC_SURVEY_URL', plugin_dir_url( __FILE__ ) );
    }

    private function include_files() {
        // Include essential plugin files
        require_once DYNAMIC_SURVEY_PATH . 'includes/class-survey-database.php';
        require_once DYNAMIC_SURVEY_PATH . 'includes/class-survey-admin.php';
        require_once DYNAMIC_SURVEY_PATH . 'includes/class-survey-frontend.php';
        require_once DYNAMIC_SURVEY_PATH . 'includes/class-survey-results.php';  
        require_once DYNAMIC_SURVEY_PATH . 'includes/class-survey-tracking.php';  
    }

    private function initialize_classes() {
        new SurveyDatabase();
        new SurveyAdmin();
        new SurveyFrontend();
        new SurveyResults();
    }

    public function activate() {
        // Create database tables during activation
        SurveyDatabase::create_tables();
        error_log("Plugin activated: Dynamic Survey and Results Visualization");
    }

    public function deactivate() {
        error_log("Plugin deactivated: Dynamic Survey and Results Visualization");
        // Any deactivation cleanup if needed
    }
}

new DynamicSurvey();
