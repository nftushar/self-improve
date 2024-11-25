<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyFrontend {
    public function __construct() {
        add_shortcode( 'dynamic_survey', [ $this, 'render_survey' ] );
        add_action( 'wp_ajax_submit_survey_vote', [ $this, 'submit_survey_vote' ] );
        add_action( 'wp_ajax_nopriv_submit_survey_vote', [ $this, 'deny_anonymous_vote' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_chartjs' ] );
    }

    public function render_survey( $atts ) {
        if ( ! is_user_logged_in() ) {
            return '<p>You must be logged in to participate in this survey.</p>';
        }

        $atts = shortcode_atts( [ 'id' => 0 ], $atts, 'dynamic_survey' );
        $survey_id = intval( $atts['id'] );

        if ( $survey_id === 0 ) {
            return '<p>Invalid survey ID.</p>';
        }

        global $wpdb;
        $survey_table = $wpdb->prefix . 'dynamic_surveys';
        $options_table = $wpdb->prefix . 'dynamic_survey_options';
        $votes_table = $wpdb->prefix . 'dynamic_survey_votes';

        $survey = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $survey_table WHERE id = %d AND status = 'open'", $survey_id ) );

        if ( ! $survey ) {
            return '<p>This survey is not available or has been closed.</p>';
        }

        $user_id = get_current_user_id();
        $has_voted = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $votes_table WHERE survey_id = %d AND user_id = %d", $survey_id, $user_id ) );

        if ( $has_voted > 0 ) {
            return $this->render_results( $survey_id ); // Display results if already voted
        }

        $options = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $options_table WHERE survey_id = %d", $survey_id ) );

        $nonce = wp_create_nonce( 'dynamic_survey_vote_nonce' ); // Generate nonce for security

        ob_start();
        ?>
        <div class="dynamic-survey">
            <h3><?php echo esc_html( $survey->question ); ?></h3>
            <form id="dynamic-survey-form" data-survey-id="<?php echo esc_attr( $survey_id ); ?>">
                <?php foreach ( $options as $option ) : ?>
                    <label>
                        <input type="radio" name="survey_option" value="<?php echo esc_attr( $option->id ); ?>" required>
                        <?php echo esc_html( $option->option_text ); ?>
                    </label><br>
                <?php endforeach; ?>
                <input type="hidden" name="nonce" value="<?php echo esc_attr( $nonce ); ?>" />
                <button type="submit">rr Submit</button>
            </form>
            <div id="survey-message" style="display: none;"></div>
            <div id="survey-results" style="display: none;">
                <canvas id="survey-chart-<?php echo esc_attr( $survey_id ); ?>" width="400" height="200"></canvas>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_results( $survey_id ) {
        error_log("Rendering result for Survey ID: $survey_id");

        global $wpdb;
        $options_table = $wpdb->prefix . 'dynamic_survey_options';
        $votes_table = $wpdb->prefix . 'dynamic_survey_votes';

        $options = $wpdb->get_results( $wpdb->prepare( "SELECT id, option_text FROM $options_table WHERE survey_id = %d", $survey_id ) );
        $votes = $wpdb->get_results( $wpdb->prepare( "SELECT option_id, COUNT(*) as vote_count FROM $votes_table WHERE survey_id = %d GROUP BY option_id", $survey_id ) );

        $vote_counts = [];
        foreach ( $votes as $vote ) {
            $vote_counts[$vote->option_id] = $vote->vote_count;
        }

        ob_start();
        ?>
        <div class="dynamic-survey-results">
            <h3>Survey Results</h3>
            <canvas id="survey-chart-<?php echo esc_attr( $survey_id ); ?>" width="400" height="200"></canvas>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    const ctx = document.getElementById('survey-chart-<?php echo esc_attr( $survey_id ); ?>').getContext('2d');
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: <?php echo json_encode( wp_list_pluck( $options, 'option_text' ) ); ?>,
                            datasets: [{
                                label: 'Votes',
                                data: <?php echo json_encode( array_map( function( $option ) use ( $vote_counts ) {
                                    return $vote_counts[$option->id] ?? 0;
                                }, $options ) ); ?>,
                                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                borderColor: 'rgba(75, 192, 192, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                });
            </script>
        </div>
        <?php
        return ob_get_clean();
    }

  public function submit_survey_vote() {
    if ( ! is_user_logged_in() ) {
        wp_send_json_error( 'You must be logged in to vote.' );
    }

    check_ajax_referer( 'dynamic_survey_vote_nonce', 'nonce' );

    global $wpdb;
    $votes_table = $wpdb->prefix . 'dynamic_survey_votes';

    $survey_id = intval( $_POST['survey_id'] );
    $option_id = intval( $_POST['option_id'] );
    $user_id = get_current_user_id();

    $has_voted = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $votes_table WHERE survey_id = %d AND user_id = %d", $survey_id, $user_id ) );

    if ( $has_voted > 0 ) {
        wp_send_json_error( 'You have already voted on this survey.' );
    }

    // Save the vote
    $wpdb->insert( $votes_table, [
        'survey_id' => $survey_id,
        'option_id' => $option_id,
        'user_id'   => $user_id,
    ]);

    // Fetch updated results
    $options_table = $wpdb->prefix . 'dynamic_survey_options';
    $votes_table = $wpdb->prefix . 'dynamic_survey_votes';

    $options = $wpdb->get_results( $wpdb->prepare( "SELECT id, option_text FROM $options_table WHERE survey_id = %d", $survey_id ) );
    $votes = $wpdb->get_results( $wpdb->prepare( "SELECT option_id, COUNT(*) as vote_count FROM $votes_table WHERE survey_id = %d GROUP BY option_id", $survey_id ) );

    $vote_counts = [];
    foreach ( $votes as $vote ) {
        $vote_counts[$vote->option_id] = $vote->vote_count;
    }

    // Prepare the chart data
    $chart_data = [
        'labels' => wp_list_pluck( $options, 'option_text' ),
        'data'   => array_map( function( $option ) use ( $vote_counts ) {
            return $vote_counts[$option->id] ?? 0;
        }, $options ),
    ];

    // Generate the HTML for the chart
    $chart_html = '<div class="dynamic-survey-results">';
    $chart_html .= '<h3>Survey Results</h3>';
    $chart_html .= '<canvas id="survey-chart-' . esc_attr( $survey_id ) . '" width="400" height="200"></canvas>';
    $chart_html .= '</div>';

    wp_send_json_success( [
        'message' => 'Thank you for your vote!',
        'results_html' => $chart_html,
        'chart_data' => $chart_data
    ] );
}

    
    

    public function deny_anonymous_vote() {
        wp_send_json_error( 'You must be logged in to vote.' );
    }
 
    public function enqueue_chartjs() {
        wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], '3.8.0', true );
        wp_enqueue_script( 'dynamic-survey', plugin_dir_url( __DIR__ ) . 'assets/js/dynamic-survey.js', ['jquery'], null, true );
    
        // Pass AJAX URL to JavaScript
        wp_localize_script( 'dynamic-survey', 'ajaxurl', admin_url( 'admin-ajax.php' ) );
    }
    
    
    
}
