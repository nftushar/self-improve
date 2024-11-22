console.log(' survey-form');


jQuery(document).ready(function($) {
    $('#dynamic-survey-form').on('submit', function(e) {
        e.preventDefault();

        var form = $(this);
        var surveyId = form.data('survey-id');
        var optionId = $('input[name="survey_option"]:checked').val();
        var nonce = form.find('input[name="nonce"]').val();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'submit_survey_vote',
                survey_id: surveyId,
                option_id: optionId,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#survey-message').html(response.data.message).show();
                    window.location.href = response.data.redirect_url; // Redirect to the results page
                } else {
                    $('#survey-message').html(response.data).show();
                }
            }
        });
    });
});
