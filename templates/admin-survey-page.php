<div class="wrap">
    <h1>Dynamic Survey</h1>

    <h2>Create a New Survey</h2>
    <form method="POST" action="<?php echo admin_url( 'admin-post.php' ); ?>">
        <?php wp_nonce_field( 'create_survey_nonce' ); ?>
        <input type="hidden" name="action" value="create_survey">

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="survey_question">Survey Question</label>
                </th>
                <td>
                    <input type="text" name="survey_question" id="survey_question" class="regular-text" required>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="survey_options">Options</label>
                </th>
                <td>
                    <div id="survey-options">
                        <input type="text" name="survey_options[]" class="regular-text" placeholder="Option 1" required>
                        <br>
                        <input type="text" name="survey_options[]" class="regular-text" placeholder="Option 2" required>
                    </div>
                    <button type="button" id="add-option" class="button">Add Option</button>
                </td>
            </tr>
        </table>

        <?php submit_button( 'Create Survey' ); ?>
    </form>

    <hr>

    <h2>All Surveys</h2>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Question</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ( ! empty( $surveys ) ) : ?>
                <?php foreach ( $surveys as $survey ) : ?>
                    <?php error_log( print_r( $survey, true ) ); // Log the survey data ?>
                    <tr>
                        <td><?php echo esc_html( $survey['id'] ); ?></td>
                        <td><?php echo esc_html( $survey['question'] ); ?></td>
                        <td><?php echo esc_html( ucfirst( $survey['status'] ) ); ?></td>
                        <td>
                            <!-- Delete button with nonce URL -->
                            <a href="<?php echo wp_nonce_url( admin_url( 'admin-post.php?action=delete_survey&survey_id=' . $survey['id'] ), 'delete_survey_nonce' ); ?>" class="button button-secondary">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="4">No surveys found.</td>
                </tr>
            <?php endif; ?>
        </tbody> 
    </table>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const addOptionButton = document.getElementById('add-option');
    const optionsContainer = document.getElementById('survey-options');

    addOptionButton.addEventListener('click', function () {
        const newInput = document.createElement('input');
        newInput.type = 'text';
        newInput.name = 'survey_options[]';
        newInput.className = 'regular-text';
        newInput.placeholder = 'Option ' + (optionsContainer.children.length + 1);
        newInput.required = true;
        optionsContainer.appendChild(document.createElement('br'));
        optionsContainer.appendChild(newInput);
    });
});
</script>
