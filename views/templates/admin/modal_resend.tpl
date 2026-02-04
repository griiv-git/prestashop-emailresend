{**
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
 *}

<div class="modal fade" id="griiv-resend-modal" tabindex="-1" role="dialog" aria-labelledby="griiv-resend-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="griiv-resend-modal-label">{l s='Email Preview & Resend' mod='griivemailresend'}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="{l s='Close' mod='griivemailresend'}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="griiv-loading" class="text-center py-4">
                    <i class="material-icons rotating">sync</i>
                    <p>{l s='Loading email content...' mod='griivemailresend'}</p>
                </div>

                <div id="griiv-content" style="display: none;">
                    <div class="form-group">
                        <label class="form-control-label">{l s='Email Preview' mod='griivemailresend'}</label>
                        <iframe id="griiv-preview-frame" sandbox="allow-same-origin" style="width:100%;height:350px;border:1px solid #ccc;background:#fff;"></iframe>
                    </div>

                    <hr>

                    <div class="form-group">
                        <label class="form-control-label" for="griiv-employee-select">{l s='Send to admin' mod='griivemailresend'}</label>
                        <select id="griiv-employee-select" class="form-control">
                            <option value="">{l s='-- Select an admin --' mod='griivemailresend'}</option>
                        </select>
                        <small class="form-text text-muted">{l s='Select a back-office admin to receive the email.' mod='griivemailresend'}</small>
                    </div>

                    <div class="form-group">
                        <label class="form-control-label" for="griiv-custom-emails">{l s='Or enter email addresses' mod='griivemailresend'}</label>
                        <input type="text" id="griiv-custom-emails" class="form-control" placeholder="{l s='Emails separated by comma (max 10)' mod='griivemailresend'}">
                        <small class="form-text text-muted">{l s='You can enter multiple email addresses separated by commas.' mod='griivemailresend'}</small>
                    </div>

                    <div class="form-group" id="griiv-attachments-group" style="display: none;">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="griiv-include-attachments" checked>
                            <label class="custom-control-label" for="griiv-include-attachments">{l s='Include original attachments' mod='griivemailresend'}</label>
                        </div>
                    </div>

                    <div id="griiv-message" class="alert" style="display: none;"></div>
                </div>

                <div id="griiv-error" class="alert alert-danger" style="display: none;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{l s='Close' mod='griivemailresend'}</button>
                <button type="button" id="griiv-send-btn" class="btn btn-primary" disabled>
                    <span class="griiv-spinner" style="display: none;">
                        <i class="material-icons rotating">sync</i>
                    </span>
                    <span class="griiv-btn-text">{l s='Send Email' mod='griivemailresend'}</span>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.rotating {
    animation: griiv-spin 1s linear infinite;
}
@keyframes griiv-spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>
