/**
 * Medical Records page — jQuery CRUD for /records.
 *
 * Extracted from resources/views/records.blade.php (was an inline <script>).
 * The store URL comes from the form's data-store-url attribute so the file
 * stays free of Blade.
 *
 * Requires: jQuery + Bootstrap 5 JS (both loaded by the page).
 */
(function ($) {
    'use strict';

    const STORE_URL = $('#recordForm').data('store-url');

    let editingId = null;   // null = adding
    let deletingId = null;

    /* ---------- helpers ---------- */

    function showAlert($target, type, message) {
        const icon = type === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill';

        $target.html(
            $('<div/>', { 'class': 'alert alert-' + type + ' alert-dismissible fade show d-flex align-items-start gap-2', role: 'alert' })
                .append($('<i/>', { 'class': 'bi bi-' + icon + ' mt-1' }))
                .append($('<div/>', { text: message }))
                .append(
                    $('<button/>', {
                        'class': 'btn-close ms-auto',
                        type: 'button',
                        'data-bs-dismiss': 'alert',
                        'aria-label': 'Close'
                    })
                )
        );
    }

    function firstErrors(xhr) {
        const errors = (xhr.responseJSON && xhr.responseJSON.errors) || {};
        const lines  = Object.values(errors).flat();

        if (lines.length) {
            return lines.join(' ');
        }

        if (xhr.status === 401) {
            return 'Your session has expired. Please sign in again.';
        }

        if (xhr.status === 404) {
            return 'That record no longer exists.';
        }

        return (xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong. Please try again.';
    }

    function updateEmptyState() {
        $('#emptyState').toggleClass('d-none', $('#recordsBody tr').length > 0);
    }

    function fileCell(record) {
        const $td = $('<td/>', { 'data-label': 'File' });

        if (!record.file_url) {
            return $td.text('—').addClass('text-muted');
        }

        return $td.append(
            $('<a/>', {
                href: record.file_url,
                target: '_blank',
                rel: 'noopener',
                'class': 'btn btn-sm btn-outline-primary btn-pill'
            }).append($('<i/>', { 'class': 'bi bi-eye me-1' })).append(document.createTextNode('View'))
        );
    }

    /** Builds (or rebuilds) the <tr> for one record. */
    function rowElement(record) {
        const $tr = $('<tr/>', {
            'data-id': record.id,
            'data-type': record.record_type || '',
            'data-description': record.description || '',
            'data-file-path': record.file_path || '',
            'data-file-url': record.file_url || '',
            'data-file-name': record.file_name || ''
        });

        $tr.append($('<td/>', { 'data-label': '#', text: record.id }));

        $tr.append(
            $('<td/>', { 'data-label': 'Type' }).append(
                $('<div/>', { 'class': 'd-flex align-items-center gap-2' })
                    .append($('<span/>', { 'class': 'record-icon' }).append($('<i/>', { 'class': 'bi bi-file-earmark-medical' })))
                    .append($('<span/>', { 'class': 'fw-semibold', text: record.record_type || '' }))
            )
        );

        const description = record.description || '—';
        $tr.append(
            $('<td/>', { 'data-label': 'Description', text: description.length > 80 ? description.slice(0, 80) + '…' : description })
        );

        $tr.append(fileCell(record));

        $tr.append($('<td/>', { 'data-label': 'Added', text: record.created_at || '—' }));

        const $actions = $('<td/>', { 'data-label': 'Actions', 'class': 'text-end' });

        $actions.append(
            $('<button/>', {
                type: 'button',
                'class': 'btn btn-sm btn-outline-primary btn-pill me-1',
                'data-bs-toggle': 'modal',
                'data-bs-target': '#recordModal'
            }).append($('<i/>', { 'class': 'bi bi-pencil' })).on('click', function () {
                openEditModal(this.closest('tr'));
            })
        );

        $actions.append(
            $('<button/>', {
                type: 'button',
                'class': 'btn btn-sm btn-outline-danger btn-pill',
                'data-bs-toggle': 'modal',
                'data-bs-target': '#deleteModal'
            }).append($('<i/>', { 'class': 'bi bi-trash' })).on('click', function () {
                openDeleteModal(this.closest('tr'));
            })
        );

        return $tr.append($actions);
    }

    /* ---------- modals ---------- */

    window.openAddModal = function () {
        editingId = null;

        const form = document.getElementById('recordForm');
        form.reset();
        $('#modalAlert').empty();
        $('#currentFileHint').addClass('d-none');

        $('#recordModalTitle').html('<i class="bi bi-file-earmark-plus me-2"></i>Add Record');
        $('#recordSubmitBtn').text('Save Record').prop('disabled', false);
    };

    window.openEditModal = function (tr) {
        if (!tr) {
            return;
        }

        const $tr = $(tr);
        editingId = $tr.data('id');

        const form = document.getElementById('recordForm');
        form.reset();
        $('#modalAlert').empty();

        $('#record_type').val($tr.data('type'));
        $('#description').val($tr.data('description'));

        const fileName = $tr.data('file-name');
        $('#currentFileHint').toggleClass('d-none', !fileName);
        $('#currentFileName').text(fileName || '');

        $('#recordModalTitle').html('<i class="bi bi-pencil me-2"></i>Edit Record');
        $('#recordSubmitBtn').text('Update Record').prop('disabled', false);
    };

    window.openDeleteModal = function (tr) {
        if (!tr) {
            return;
        }

        const $tr = $(tr);

        deletingId = $tr.data('id');
        $('#deleteRecordType').text($tr.data('type') || 'this record');
        $('#deleteConfirmBtn').prop('disabled', false);
    };

    /* ---------- CRUD ---------- */

    $('#recordForm').on('submit', function (event) {
        event.preventDefault();

        const formData = new FormData(this);
        const isEdit  = editingId !== null;

        if (isEdit) {
            // Method spoofing: multipart PUT bodies are not parsed by PHP.
            formData.append('_method', 'PUT');
        }

        const url = isEdit ? STORE_URL + '/' + editingId : STORE_URL;

        $('#recordSubmitBtn').prop('disabled', true).text(isEdit ? 'Updating…' : 'Saving…');

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                const row = rowElement(response.record);
                const existing = $('#recordsBody tr[data-id="' + response.record.id + '"]');

                if (existing.length) {
                    existing.replaceWith(row);
                } else {
                    $('#recordsBody').prepend(row);
                }

                updateEmptyState();

                bootstrap.Modal.getOrCreateInstance(document.getElementById('recordModal')).hide();
                showAlert($('#recordsAlert'), 'success', response.message);
            },
            error: function (xhr) {
                showAlert($('#modalAlert'), 'danger', firstErrors(xhr));
                $('#recordSubmitBtn').prop('disabled', false).text(isEdit ? 'Update Record' : 'Save Record');
            }
        });
    });

    $('#deleteConfirmBtn').on('click', function () {
        if (deletingId === null) {
            return;
        }

        const id = deletingId;
        $(this).prop('disabled', true);

        $.ajax({
            url: STORE_URL + '/' + id,
            type: 'POST',
            data: { _method: 'DELETE' },
            dataType: 'json',
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function (response) {
                $('#recordsBody tr[data-id="' + id + '"]').remove();
                updateEmptyState();

                bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteModal')).hide();
                showAlert($('#recordsAlert'), 'success', response.message);
            },
            error: function (xhr) {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteModal')).hide();
                showAlert($('#recordsAlert'), 'danger', firstErrors(xhr));
            },
            complete: function () {
                deletingId = null;
                $('#deleteConfirmBtn').prop('disabled', false);
            }
        });
    });
})(jQuery);
