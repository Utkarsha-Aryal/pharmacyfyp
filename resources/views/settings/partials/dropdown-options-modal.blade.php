<div class="modal fade" id="dropdownOptionModal" tabindex="-1" aria-labelledby="dropdownOptionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form
                id="dropdownOptionForm"
                data-list-url="{{ route('admin.settings.options.index') }}"
                data-store-url="{{ route('admin.settings.options.store') }}"
                data-update-url-template="{{ url('admin/settings/options/__ID__') }}"
                data-delete-url-template="{{ url('admin/settings/options/__ID__') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="dropdownOptionModalLabel">Dropdown Option</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="dropdown_option_id">
                    <input type="hidden" name="alias" id="dropdown_option_alias">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" name="name" id="dropdown_option_name" class="form-control" required>
                    </div>
                    <div class="mb-3" id="dropdownOptionDataWrap">
                        <label class="form-label">Data</label>
                        <input type="text" name="data" id="dropdown_option_data" class="form-control" placeholder="Optional metadata like cash, digital or bank">
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="dropdown_option_status" checked>
                        <label class="form-check-label" for="dropdown_option_status">Active</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="dropdownOptionSubmitBtn">
                        <i class="fa-solid fa-save me-1"></i> Save
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
