<?php

if ( ! defined( 'ABSPATH' ) ) exit;

class SurveyShortcode {

    public function __construct() {
        add_shortcode( 'dynamic_survey', [ $this, 'render_survey_shortcode' ] ); 
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] ); 
        add_action( 'wp_ajax_submit_survey_vote', [ $this, 'handle_survey_submission' ] );
        add_action( 'wp_ajax_nopriv_submit_survey_vote', [ $this, 'handle_survey_submission' ] );
    }

    public function render_survey_shortcode( $atts ) {
        global $wpdb;
        $atts = shortcode_atts( [ 'id' => 0 ], $atts, 'dynamic_survey' );
        $survey_id = intval( $atts['id'] );

        if ( ! $survey_id ) {
            return '<p>Invalid survey ID.</p>';
        }

        $survey_table = $wpdb->prefix . 'dynamic_surveys';
        $options_table = $wpdb->prefix . 'dynamic_survey_options';
        $votes_table = $wpdb->prefix . 'dynamic_survey_votes';

        $survey = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $survey_table WHERE id = %d", $survey_id ) );
        if ( ! $survey || $survey->status === 'closed' ) {
            return '<p>This survey is not available.</p>';
        }

        $options = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $options_table WHERE survey_id = %d", $survey_id ) );

        $user_id = get_current_user_id();
        $has_voted = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $votes_table WHERE survey_id = %d AND user_id = %d", $survey_id, $user_id ) );

        ob_start();
        if ( $has_voted ) {
            echo '<p>Thank you for voting! Here are the results:</p>';
            $this->render_chart( $survey_id );
        } else {
            ?>
            <form id="dynamic-survey-form">
                <p><?php echo esc_html( $survey->question ); ?></p>
                <?php foreach ( $options as $option ) : ?>
                    <label>
                        <input type="radio" name="survey_option" value="<?php echo esc_attr( $option->id ); ?>" required>
                        <?php echo esc_html( $option->option_text ); ?>
                    </label>
                    <br>
                <?php endforeach; ?>
                <input type="hidden" name="survey_id" value="<?php echo esc_attr( $survey_id ); ?>">
                <button type="submit" class="button">xxSubmit</button>
            </form>
            <div id="dynamic-survey-result"></div>
            <?php
        }
        return ob_get_clean();
    }

    private function render_chart( $survey_id ) {
        global $wpdb;
        $options_table = $wpdb->prefix . 'dynamic_survey_options';
        $votes_table = $wpdb->prefix . 'dynamic_survey_votes';

        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT o.option_text, COUNT(v.id) as votes 
             FROM $options_table o
             LEFT JOIN $votes_table v ON o.id = v.option_id
             WHERE o.survey_id = %d
             GROUP BY o.id",
            $survey_id
        ) );

        $labels = [];
        $data = [];
        foreach ( $results as $result ) {
            $labels[] = $result->option_text;
            $data[] = $result->votes;
        }

        ?>
        <canvas id="survey-chart-<?php echo esc_attr( $survey_id ); ?>"></canvas>
        <script>
            const ctx = document.getElementById('survey-chart-<?php echo esc_attr( $survey_id ); ?>').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode( $labels ); ?>,
                    datasets: [{
                        label: 'Votes',
                        data: <?php echo json_encode( $data ); ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                }
            });
        </script>
        <?php
    }

    public function enqueue_scripts() {
        // Enqueue the main survey script
        wp_enqueue_script( 'dynamic-survey', plugins_url( 'assets/js/survey.js', DYNAMIC_SURVEY_PATH ), [ 'jquery' ], '1.0', true );
        
        // Properly localize the script
        wp_localize_script( 'dynamic-survey', 'surveyAjax', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'survey_nonce' ),  // Adding a nonce for security
        ]);
    
        // Enqueue Chart.js
        wp_enqueue_script( 'chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', [], null, true );
    }
 
    

    public function handle_survey_submission() {
        if ( ! isset( $_POST['survey_id'], $_POST['option_id'] ) || ! is_user_logged_in() ) {
            wp_send_json_error( 'Invalid request.' );
        }

        global $wpdb;
        $votes_table = $wpdb->prefix . 'dynamic_survey_votes';

        $survey_id = intval( $_POST['survey_id'] );
        $option_id = intval( $_POST['option_id'] );
        $user_id = get_current_user_id();

        // Prevent duplicate voting
        $has_voted = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $votes_table WHERE survey_id = %d AND user_id = %d", $survey_id, $user_id ) );
        if ( $has_voted ) {
            wp_send_json_error( 'You have already voted.' );
        }

        $wpdb->insert( $votes_table, [
            'survey_id' => $survey_id,
            'option_id' => $option_id,
            'user_id'   => $user_id,
        ]);

        wp_send_json_success( 'Vote submitted successfully.' );
    }
}
