<div class="modal fade" id="modalAlert" tabindex="-1" aria-labelledby="modalAlertLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header" style="border-bottom:none;">
                <h4 class="modal-title" id="modalAlertLabel" data-default-text="{{ __('admin.alert') }}">{{ __('admin.alert') }}</h4>
            </div>
            <div class="modal-body text-center"></div>
            <div class="modal-footer justify-content-center"  style="border-top:none;">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>
<script>
    window.addEventListener('DOMContentLoaded', () => {

        const alertModal = new bootstrap.Modal(document.getElementById('modalAlert'), {backdrop: 'static'});

        window.alert_native = window.alert;
        window.alert = function alert(content, title = null) {
            if (title === true) {
                window.alert_native(content);
                return;
            }
            let modalNode = document.getElementById('modalAlert');
            let titleNode = modalNode.querySelector('.modal-title');
            let bodyNode = modalNode.querySelector('.modal-body');

            bodyNode.innerHTML = (content) ? content : '';
            titleNode.textContent = (title !== null) ? title : titleNode.dataset.defaultText;

            alertModal.show()
        }
    });
</script>
