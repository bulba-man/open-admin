<div class="modal fade" id="modalPrompt" tabindex="-1" aria-labelledby="modalPromptLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modalPromptLabel"></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('admin.close') }}"></button>
            </div>
            <div class="modal-body">
                <div class="content-block"></div>
                <div class="form-block"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-action="confirm" >{{ __('admin.save') }}</button>
                <button type="button" class="btn btn-danger" data-action="cancel" >{{ __('admin.cancel') }}</button>
                <button type="button" class="btn btn-light" data-action="close" >{{ __('admin.close') }}</button>
            </div>
        </div>
    </div>
</div>
