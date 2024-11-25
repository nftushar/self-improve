console.log('dynamic-survey-form');

jQuery(document).ready(function ($) {
    $('#dynamic-survey-form').on('submit', function (e) {
        e.preventDefault();

        const surveyId = $(this).data('survey-id');
        const selectedOption = $('input[name="survey_option"]:checked').val();
        const nonce = $('input[name="nonce"]').val();

        if (!selectedOption) {
            alert('Please select an option to vote.');
            return;
        }

        $.ajax({
            url: ajaxurl, // Ensure `ajaxurl` is defined globally (for logged-in users)
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'submit_survey_vote',
                survey_id: surveyId,
                option_id: selectedOption,
                nonce: nonce,
            },
            success: function (response) {
                if (response.success) {
                    // Replace survey form with results chart
                    $('#dynamic-survey-form').replaceWith(response.data.results_html);

                    // Render the chart
                    const ctx = document.getElementById(`survey-chart-${surveyId}`).getContext('2d');
                    new Chart(ctx, {
                        type: 'bar', // Change to 'pie' for a Pie Chart
                        data: {
                            labels: response.data.chart_data.labels,
                            datasets: [{
                                label: 'Votes',
                                data: response.data.chart_data.data,
                                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                                borderColor: 'rgba(75, 192, 192, 1)',
                                borderWidth: 1,
                            }],
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: {
                                    beginAtZero: true,
                                },
                            },
                        },
                    });

                    // Show success message
                    $('#survey-message').text(response.data.message).fadeIn();
                } else {
                    alert(response.data || 'An error occurred.');
                }
            },
            error: function () {
                alert('An unexpected error occurred. Please try again.');
            },
        });
    });
});

