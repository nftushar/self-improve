<div class="wrap">
    <h1>Survey Voting Tracking</h1>

    <?php 
    global $wpdb;

    // Get all votes along with survey question and user name
    $votes = $wpdb->get_results( "
        SELECT v.id, v.vote_time, v.user_ip, 
               s.question AS survey_question, 
               u.display_name AS user_name
        FROM {$wpdb->prefix}dynamic_survey_votes v
        JOIN {$wpdb->prefix}dynamic_surveys s ON v.survey_id = s.id
        LEFT JOIN {$wpdb->users} u ON v.user_id = u.ID
    " );
    ?>

    <?php if ( empty( $votes ) ) : ?>
        <p>No votes found.</p>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Survey</th>
                    <th>User</th>
                    <th>Vote Time</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $votes as $vote ) : ?>
                    <tr>
                        <td><?php echo esc_html( $vote->survey_question ); ?></td>
                        <td><?php echo esc_html( $vote->user_name ); ?></td>
                        <td><?php echo esc_html( $vote->vote_time ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
