/**
 * Copyright since 2024 Griiv
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 *
 * @author    Griiv
 * @copyright Since 2024 Griiv
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License ("AFL") v. 3.0
 */

$(document).ready(function() {
    var currentIdMail = null;
    var employeesLoaded = false;

    // Intercept click on resend button
    $(document).on('click', '.griiv-resend-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();

        // Get id_mail from data attribute or from row
        var idMail = $(this).data('id-mail');
        if (!idMail) {
            // Try to get from the row
            var $row = $(this).closest('tr');
            idMail = $row.find('td:first').text().trim();
        }

        if (idMail) {
            loadEmailPreview(parseInt(idMail, 10));
        }
    });

    // Load email preview
    function loadEmailPreview(idMail) {
        currentIdMail = idMail;

        // Reset modal state
        $('#griiv-loading').show();
        $('#griiv-content').hide();
        $('#griiv-error').hide();
        $('#griiv-message').hide();
        $('#griiv-send-btn').prop('disabled', true);
        $('#griiv-employee-select').val('');
        $('#griiv-custom-emails').val('');
        $('#griiv-include-attachments').prop('checked', true);

        // Show modal
        $('#griiv-resend-modal').modal('show');

        // Load employees if not loaded yet
        if (!employeesLoaded) {
            loadEmployees();
        }

        // Load email content
        $.ajax({
            url: griivResendUrls.getContent,
            type: 'POST',
            dataType: 'json',
            data: {
                id_mail: idMail
            },
            success: function(response) {
                if (response.success) {
                    // Write HTML content to iframe securely using srcdoc
                    var iframe = document.getElementById('griiv-preview-frame');
                    iframe.srcdoc = response.html_content;

                    // Show/hide attachments checkbox
                    if (response.has_attachments) {
                        $('#griiv-attachments-group').show();
                    } else {
                        $('#griiv-attachments-group').hide();
                    }

                    $('#griiv-loading').hide();
                    $('#griiv-content').show();
                    $('#griiv-send-btn').prop('disabled', false);
                } else {
                    showError(response.message || griivResendTranslations.error);
                }
            },
            error: function() {
                showError(griivResendTranslations.error);
            }
        });
    }

    // Load employees list
    function loadEmployees() {
        $.ajax({
            url: griivResendUrls.getEmployees,
            type: 'POST',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.employees) {
                    var $select = $('#griiv-employee-select');
                    $select.find('option:not(:first)').remove();

                    $.each(response.employees, function(i, emp) {
                        $select.append(
                            $('<option></option>')
                                .val(emp.email)
                                .text(emp.name + ' (' + emp.email + ')')
                        );
                    });

                    employeesLoaded = true;
                }
            }
        });
    }

    // Send email
    $('#griiv-send-btn').on('click', function() {
        var $btn = $(this);
        if ($btn.prop('disabled')) {
            return;
        }

        // Collect recipients
        var emails = [];
        var selectedEmployee = $('#griiv-employee-select').val();
        if (selectedEmployee) {
            emails.push(selectedEmployee);
        }

        var customEmails = $('#griiv-custom-emails').val();
        if (customEmails) {
            var customList = customEmails.split(',');
            for (var i = 0; i < customList.length; i++) {
                var email = customList[i].trim();
                if (email) {
                    emails.push(email);
                }
            }
        }

        // Validate
        if (emails.length === 0) {
            showMessage('danger', griivResendTranslations.noRecipient);
            return;
        }
        if (emails.length > 10) {
            showMessage('danger', griivResendTranslations.maxRecipients);
            return;
        }

        // Simple email validation
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        for (var j = 0; j < emails.length; j++) {
            if (!emailRegex.test(emails[j])) {
                showMessage('danger', griivResendTranslations.invalidEmail + ': ' + emails[j]);
                return;
            }
        }

        // Disable button and show spinner
        $btn.prop('disabled', true);
        $btn.find('.griiv-spinner').show();
        $btn.find('.griiv-btn-text').text(griivResendTranslations.sending);

        // Send request
        $.ajax({
            url: griivResendUrls.resend,
            type: 'POST',
            dataType: 'json',
            data: {
                id_mail: currentIdMail,
                emails: emails,
                include_attachments: $('#griiv-include-attachments').is(':checked') ? 1 : 0
            },
            success: function(response) {
                if (response.success) {
                    showMessage('success', response.message || griivResendTranslations.sent);
                    setTimeout(function() {
                        $('#griiv-resend-modal').modal('hide');
                    }, 2000);
                } else {
                    showMessage('danger', response.message || griivResendTranslations.sendError);
                }
            },
            error: function() {
                showMessage('danger', griivResendTranslations.error);
            },
            complete: function() {
                $btn.prop('disabled', false);
                $btn.find('.griiv-spinner').hide();
                $btn.find('.griiv-btn-text').text('Send Email');
            }
        });
    });

    // Show error in modal
    function showError(message) {
        $('#griiv-loading').hide();
        $('#griiv-content').hide();
        $('#griiv-error').text(message).show();
    }

    // Show message in content area
    function showMessage(type, message) {
        $('#griiv-message')
            .removeClass('alert-success alert-danger alert-warning')
            .addClass('alert-' + type)
            .text(message)
            .show();
    }

    // Reset modal when hidden
    $('#griiv-resend-modal').on('hidden.bs.modal', function() {
        currentIdMail = null;
        $('#griiv-loading').show();
        $('#griiv-content').hide();
        $('#griiv-error').hide();
        $('#griiv-message').hide();
        var iframe = document.getElementById('griiv-preview-frame');
        if (iframe) {
            iframe.srcdoc = '';
        }
    });
});
