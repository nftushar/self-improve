console.log(' -my survey-form');

jQuery(document).ready(function($) {
    $('#dynamic-survey-form').on('submit', function(e) {
        e.preventDefault();

        var form = $(this);
        var survey_id = form.data('survey-id');
        var option_id = $('input[name="survey_option"]:checked', form).val();
        var nonce = $('input[name="nonce"]', form).val();

        // Send the AJAX request
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'submit_survey_vote',
                survey_id: survey_id,
                option_id: option_id,
                nonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#survey-message').text(response.data.message).show();
                    $('#survey-results').html(response.data.results_html).show();

                    // Render chart using the returned chart data
                    var ctx = document.getElementById('survey-chart-' + survey_id).getContext('2d');
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: response.data.chart_data.labels,
                            datasets: [{
                                label: 'Votes',
                                data: response.data.chart_data.data,
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
                } else {
                    $('#survey-message').text(response.data.message).show();
                }
            }
        });
    });
});
