(function($) {
    'use strict';

    $(document).ready(function() {
        const $form = $('#novac-payment-form');
        const $messages = $form.find('.novac-form-messages');
        const $submitBtn = $form.find('.novac-submit-btn');
        const originalBtnText = $submitBtn.text();

        $form.on('submit', function(e) {
            e.preventDefault();

            // Clear previous messages
            $messages.removeClass('show error success info').text('');

            // Disable submit button
            $submitBtn.prop('disabled', true).html(originalBtnText + ' <span class="novac-loading-spinner"></span>');

            // Get form data
            const formData = $form.serialize();

            console.log(formData);

            // Make AJAX request
            $.ajax({
                url: novacFrontend.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success && response.data.checkout_url) {
                        // Show success message
                        $messages.addClass('show info').text('Redirecting to payment gateway...');
                        
                        // Redirect to checkout URL
                        window.location.href = response.data.checkout_url;
                    } else {
                        // Show error message
                        const errorMsg = response.data && response.data.message 
                            ? response.data.message 
                            : 'An error occurred. Please try again.';
                        $messages.addClass('show error').text(errorMsg);
                        $submitBtn.prop('disabled', false).text(originalBtnText);
                    }
                },
                error: function(xhr) {
                    let errorMsg = 'An error occurred. Please try again.';
                    
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMsg = xhr.responseJSON.data.message;
                    }
                    
                    $messages.addClass('show error').text(errorMsg);
                    $submitBtn.prop('disabled', false).text(originalBtnText);
                }
            });
        });

        // Handle payment status messages in URL
        const urlParams = new URLSearchParams(window.location.search);
        const paymentStatus = urlParams.get('novac-payment');
        const reference = urlParams.get('reference');

        if (paymentStatus && reference) {
            let message = '';
            let messageClass = 'info';

            switch(paymentStatus) {
                case 'success':
                case 'successful':
                    message = 'Payment successful! Reference: ' + reference;
                    messageClass = 'success';
                    break;
                case 'failed':
                    message = 'Payment failed. Please try again. Reference: ' + reference;
                    messageClass = 'error';
                    break;
                case 'pending':
                    message = 'Payment is pending. Reference: ' + reference;
                    messageClass = 'info';
                    break;
                default:
                    message = 'Payment status: ' + paymentStatus + '. Reference: ' + reference;
            }

            $messages.addClass('show ' + messageClass).text(message);
        }
    });

})(jQuery);
