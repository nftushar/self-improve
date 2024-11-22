console.log('dynamic-survey-form');

jQuery(document).ready(function($) {
    $('#dynamic-survey-form').on('submit', function(e) {
        e.preventDefault();

        var survey_id = $(this).data('survey-id');
        var option_id = $('input[name="survey_option"]:checked').val();
        var nonce = $('input[name="nonce"]').val();

        // Send the AJAX request
        $.ajax({
            url: ajaxurl, // This will now use the localized `ajaxurl` value
            method: 'POST',
            data: {
                action: 'submit_survey_vote',
                survey_id: survey_id,
                option_id: option_id,
                nonce: nonce,
            },
            success: function(response) {
                if (response.success) {
                    // Replace the survey with the results chart
                    $('#survey-message').text(response.data.message).show();
                    $('#survey-results').html(response.data.results_html).show();

                    // Optional: Log chart data for debugging
                    console.log(response.data.chart_data);
                } else {
                    $('#survey-message').text(response.data.message).show();
                }
            },
            error: function() {
                $('#survey-message').text('There was an error processing your vote.').show();
            }
        });
    });
});
