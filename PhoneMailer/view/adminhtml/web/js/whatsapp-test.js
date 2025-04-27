define([
    'jquery',
    'mage/translate'
], function ($, $t) {
    'use strict';
    
    return function (config) {
        $(config.buttonId).on('click', function () {
            var apiKey = $(config.apiKeyField).val();
            var instanceId = $(config.instanceIdField).val();
            
            if (!apiKey || !instanceId) {
                alert($t('Please enter API Key and Instance ID before testing the connection.'));
                return;
            }
            
            // Show loading state
            $(config.buttonId).prop('disabled', true).text($t('Testing...'));
            $(config.resultContainer).hide();
            
            // Send AJAX request
            $.ajax({
                url: config.testUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    api_key: apiKey,
                    instance_id: instanceId,
                    form_key: window.FORM_KEY
                },
                success: function (response) {
                    // Reset button
                    $(config.buttonId).prop('disabled', false).text($t('Test Connection'));
                    
                    // Show result
                    if (response.success) {
                        $(config.successContainer).show().find(config.successMessage).text(response.message);
                        $(config.errorContainer).hide();
                    } else {
                        $(config.errorContainer).show().find(config.errorMessage).text(response.message);
                        $(config.successContainer).hide();
                    }
                    
                    $(config.resultContainer).show();
                },
                error: function () {
                    // Reset button
                    $(config.buttonId).prop('disabled', false).text($t('Test Connection'));
                    
                    // Show error
                    $(config.errorContainer).show().find(config.errorMessage).text($t('An error occurred while testing the connection.'));
                    $(config.successContainer).hide();
                    $(config.resultContainer).show();
                }
            });
        });
    };
});
