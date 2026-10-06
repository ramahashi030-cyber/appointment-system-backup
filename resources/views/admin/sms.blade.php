@extends('layouts.admin')

@section('title', 'SMS Module')

@section('sidebar')
    @include('partials.admin-sidebar')
@endsection

@section('header')
    @include('partials.admin-header')
@endsection

@section('content')
    @php
        $hasFilters = $filters['search'] !== ''
            || $filters['service'] !== ''
            || $filters['from_date'] !== ''
            || $filters['to_date'] !== '';

        // Paginate the already-fetched booked-patient collection in the view so a
        // large result set never renders as one long scrollable table. The
        // controller query, filters, and search are left untouched.
        $smsPerPage = 20;
        $smsCurrentPage = \Illuminate\Pagination\LengthAwarePaginator::resolveCurrentPage();
        // The Booked Patients table only renders results once a filter/search is
        // applied; an unfiltered first load shows an empty table. The full
        // collection is still used below to resolve SMS-history patient names.
        $bookedResults = $hasFilters ? $appointments : collect();

        $appointmentsPage = new \Illuminate\Pagination\LengthAwarePaginator(
            $bookedResults->forPage($smsCurrentPage, $smsPerPage)->values(),
            $bookedResults->count(),
            $smsPerPage,
            $smsCurrentPage,
            ['path' => request()->url(), 'query' => request()->query()],
        );

        // SMS logs store only the digits-only recipient, so resolve the patient
        // name for the history table from the booked patients' contact numbers.
        $smsRecipientNames = $appointments->mapWithKeys(function ($appointment): array {
            $patient = $appointment->patient;
            $contact = preg_replace('/\D+/', '', (string) ($patient?->contact_number ?? ''));

            return $contact === '' || $patient === null
                ? []
                : [$contact => $patient->full_name ?? 'Unknown patient'];
        });
    @endphp

    <div class="admin-dashboard-content admin-doctor-content admin-sms-page">
        <section class="admin-telemedicine-banner admin-doctor-banner" aria-labelledby="smsModuleTitle">
            <div class="admin-telemedicine-glow" aria-hidden="true"></div>
            <div class="admin-telemedicine-content">
                <div class="admin-telemedicine-mark" aria-hidden="true">
                    <i class="bi bi-phone-fill"></i>
                    <span><i class="bi bi-chat-dots-fill"></i></span>
                </div>
                <div class="admin-telemedicine-copy">
                    <h1 id="smsModuleTitle">SMS Module</h1>
                    <p class="admin-telemedicine-welcome">Patient Messaging</p>
                    <p class="admin-telemedicine-description">Send text messages to patients with a booked appointment.</p>
                    <div class="admin-telemedicine-trust" aria-label="SMS module features">
                        <span><i class="bi bi-people-fill" aria-hidden="true"></i> Booked Patients</span>
                        <b aria-hidden="true">&bull;</b>
                        <span>Bulk Messaging</span>
                        <b aria-hidden="true">&bull;</b>
                        <span>Delivery History</span>
                    </div>
                </div>
            </div>
        </section>

        @if ($dateError)
            <div class="alert alert-danger d-flex align-items-start gap-2" role="alert">
                <i class="bi bi-exclamation-triangle-fill mt-1" aria-hidden="true"></i>
                <div>{{ $dateError }}</div>
            </div>
        @endif

        <section class="admin-panel admin-doctor-roster-panel admin-sms-panel" aria-labelledby="smsRosterTitle">
            <header class="admin-panel-header admin-doctor-roster-header">
                <div class="admin-panel-title">
                    <i class="bi bi-people-fill" aria-hidden="true"></i>
                    <h2 id="smsRosterTitle">Booked Patients</h2>
                </div>
                <span class="admin-muted-text">{{ $appointmentsPage->total() }} booked patient{{ $appointmentsPage->total() === 1 ? '' : 's' }}</span>
            </header>

            <div class="admin-sms-toolbar">
                <form class="admin-doctor-filters" method="GET" action="{{ route('admin.sms') }}">
                    <input type="hidden" name="search" value="{{ $filters['search'] }}">
                    <select class="form-select" name="service" aria-label="Filter by type of service">
                        <option value="">All types of service</option>
                        @if (($serviceOptions['FACE'] ?? collect())->isNotEmpty())
                            <optgroup label="Face to Face">
                                @foreach ($serviceOptions['FACE'] as $id => $name)
                                    <option value="FACE:{{ $id }}" @selected($filters['service'] === "FACE:{$id}")>{{ $name }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                        @if (($serviceOptions['TELE'] ?? collect())->isNotEmpty())
                            <optgroup label="Telemedicine">
                                @foreach ($serviceOptions['TELE'] as $id => $name)
                                    <option value="TELE:{{ $id }}" @selected($filters['service'] === "TELE:{$id}")>{{ $name }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                    </select>
                    <input type="date" class="form-control" name="from_date" value="{{ $filters['from_date'] }}" aria-label="Filter by date from" style="width: auto;">
                    <input type="date" class="form-control" name="to_date" value="{{ $filters['to_date'] }}" aria-label="Filter by date to" style="width: auto;">
                    <button type="submit" class="admin-primary-button">
                        <i class="bi bi-funnel-fill" aria-hidden="true"></i>
                        <span>Filter</span>
                    </button>
                    @if ($hasFilters)
                        <a class="admin-clear-filter" href="{{ route('admin.sms') }}">Clear</a>
                    @endif
                </form>

                <form class="admin-doctor-filters" method="GET" action="{{ route('admin.sms') }}">
                    <input type="hidden" name="service" value="{{ $filters['service'] }}">
                    <input type="hidden" name="from_date" value="{{ $filters['from_date'] }}">
                    <input type="hidden" name="to_date" value="{{ $filters['to_date'] }}">
                    <div class="admin-doctor-search">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <label class="visually-hidden" for="smsSearch">Search by last or first name</label>
                        <input id="smsSearch" name="search" value="{{ $filters['search'] }}" placeholder="Search by Last or First Name...">
                    </div>
                    <button type="submit" class="admin-primary-button">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <span>Search</span>
                    </button>
                </form>
            </div>

            <form id="smsSendForm" method="POST" action="{{ route('admin.sms.send') }}">
                @csrf

                <div class="admin-doctor-table-wrap">
                    <table class="admin-doctor-table admin-sms-booked-table">
                        <caption class="visually-hidden">Booked patients</caption>
                        <thead>
                            <tr>
                                <th scope="col">
                                    <input type="checkbox" id="smsSelectAll" aria-label="Select all booked patients">
                                </th>
                                <th scope="col">Patient Name</th>
                                <th scope="col">Date of Birth</th>
                                <th scope="col">Contact Number</th>
                                <th scope="col">Type of Service</th>
                                <th scope="col">Appointment Date</th>
                                <th scope="col">Appointment Slot</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($appointmentsPage as $appointment)
                                @php
                                    $patient = $appointment->patient;
                                    $contact = trim((string) ($patient?->contact_number ?? ''));
                                    $initials = strtoupper(substr((string) ($patient?->first_name ?: 'P'), 0, 1).substr((string) ($patient?->last_name ?: 'A'), 0, 1));
                                @endphp
                                <tr>
                                    <td>
                                        <input type="checkbox" class="sms-row-checkbox" name="appointments[]" value="{{ $appointment->id }}" aria-label="Select {{ $patient?->full_name ?? 'patient' }}">
                                    </td>
                                    <td>
                                        <div class="admin-doctor-person">
                                            <span class="admin-avatar" aria-hidden="true">{{ $initials }}</span>
                                            <span>
                                                <strong>{{ $patient?->full_name ?? 'Unknown patient' }}</strong>
                                            </span>
                                        </div>
                                    </td>
                                    <td>{{ $patient?->dob?->format('M j, Y') ?: '—' }}</td>
                                    <td class="admin-sms-col-contact">
                                        @if ($contact !== '')
                                            <span class="admin-doctor-primary-text">{{ $contact }}</span>
                                        @else
                                            <span class="admin-status-pill cancelled">No contact number</span>
                                        @endif
                                    </td>
                                    <td>{{ $appointment->service_name ?: '—' }}</td>
                                    <td>{{ $appointment->date?->format('M j, Y') ?: '—' }}</td>
                                    <td>{{ $appointment->time_slot ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">
                                        <div class="admin-doctor-empty">
                                            <i class="bi bi-chat-dots" aria-hidden="true"></i>
                                            <strong>No patient found</strong>
                                            <span>Please apply a filter to view booked patients.</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($appointmentsPage->hasPages())
                    <div class="admin-doctor-pagination">
                        @if ($appointmentsPage->onFirstPage())
                            <span class="admin-doctor-page-button is-disabled" aria-disabled="true">&laquo;</span>
                        @else
                            <a class="admin-doctor-page-button" href="{{ $appointmentsPage->previousPageUrl() }}" aria-label="Previous page">&laquo;</a>
                        @endif
                        <span class="admin-doctor-page-current" aria-current="page">{{ $appointmentsPage->currentPage() }}</span>
                        @if ($appointmentsPage->hasMorePages())
                            <a class="admin-doctor-page-button" href="{{ $appointmentsPage->nextPageUrl() }}" aria-label="Next page">&raquo;</a>
                        @else
                            <span class="admin-doctor-page-button is-disabled" aria-disabled="true">&raquo;</span>
                        @endif
                    </div>
                @endif

                <div class="admin-sms-actions">
                    <button type="button" class="admin-primary-button" id="smsSendSelected">
                        <i class="bi bi-send-fill" aria-hidden="true"></i>
                        <span>Send Message to Selected</span>
                    </button>
                    <span class="admin-sms-notice" id="smsSelectionNotice" role="alert" hidden>Please select at least one patient.</span>
                </div>
            </form>
        </section>

        <section class="admin-panel admin-doctor-roster-panel admin-sms-panel" aria-labelledby="smsHistoryTitle">
            <header class="admin-panel-header admin-doctor-roster-header">
                <div class="admin-panel-title">
                    <i class="bi bi-clock-history" aria-hidden="true"></i>
                    <h2 id="smsHistoryTitle">Recently Sent Messages</h2>
                </div>
                <span class="admin-muted-text">{{ $recentMessages->count() }} message{{ $recentMessages->count() === 1 ? '' : 's' }}</span>
            </header>

            <div class="admin-doctor-table-wrap">
                <table class="admin-doctor-table admin-sms-history-table">
                    <caption class="visually-hidden">Recently sent messages</caption>
                    <thead>
                        <tr>
                            <th scope="col" class="admin-sms-col-patient">Patient Name</th>
                            <th scope="col" class="admin-sms-col-recipient">Recipient</th>
                            <th scope="col" class="admin-sms-col-message">Message</th>
                            <th scope="col" class="admin-sms-col-sent">Sent At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentMessages as $message)
                            <tr>
                                @php
                                    $recipientKey = preg_replace('/\D+/', '', (string) $message->recipient);
                                    $patientName = $recipientKey !== '' ? ($smsRecipientNames[$recipientKey] ?? null) : null;
                                @endphp
                                <td class="admin-sms-col-patient">
                                    @if ($patientName)
                                        <span class="admin-doctor-primary-text">{{ $patientName }}</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="admin-sms-col-recipient"><span class="admin-doctor-primary-text">{{ $message->recipient }}</span></td>
                                <td class="admin-sms-col-message">{{ $message->message }}</td>
                                <td class="admin-sms-col-sent">{{ $message->sent_at?->copy()->timezone($timezone)->format('m/d/Y h:i A') ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="admin-doctor-empty">
                                        <i class="bi bi-envelope-open" aria-hidden="true"></i>
                                        <strong>No messages sent yet</strong>
                                        <span>Messages you send to booked patients will appear here.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="modal fade admin-doctor-modal" id="smsMessageModal" tabindex="-1" aria-labelledby="smsMessageModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <header class="modal-header admin-doctor-modal-header">
                    <div class="admin-telemedicine-glow" aria-hidden="true"></div>
                    <div class="admin-telemedicine-content">
                        <div class="admin-telemedicine-mark" aria-hidden="true">
                            <i class="bi bi-chat-dots-fill"></i>
                        </div>
                        <div class="admin-telemedicine-copy">
                            <h2 class="modal-title" id="smsMessageModalTitle">Send Message</h2>
                            <p class="admin-telemedicine-description">Compose one message for the selected patients.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </header>
                <div class="modal-body">
                    <div class="admin-doctor-form">
                        <div class="admin-panel">
                            <div class="admin-panel-title">
                                <i class="bi bi-people-fill" aria-hidden="true"></i>
                                <h3>Recipients</h3>
                            </div>
                            <p class="admin-muted-text">Selected recipients: <strong id="smsRecipientCount">0</strong> patients</p>
                            <div class="form-field">
                                <label for="smsMessage">Message <span>*</span></label>
                                <textarea class="form-control" id="smsMessage" name="message" form="smsSendForm" rows="4" maxlength="1000" placeholder="Type your message..." required></textarea>
                                <small class="admin-muted-text">Tip: use <code>patientname</code> to insert each patient's name.</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="admin-secondary-button" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="admin-primary-button" form="smsSendForm">
                        <i class="bi bi-send-fill" aria-hidden="true"></i>
                        <span>Send Message</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        /* ===== SMS Module styling — scoped to this page only ===== */

        /* Breathing room between the stacked dashboard cards */
        .admin-sms-page .admin-sms-panel + .admin-sms-panel {
            margin-top: 18px;
        }

        /* Single, unified filter/search toolbar (the two GET forms render as one card row) */
        .admin-sms-page .admin-sms-toolbar {
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding: 15px 20px;
            border-bottom: 1px solid #e4edf7;
            background: #fbfdff;
        }

        .admin-sms-page .admin-sms-toolbar .admin-doctor-filters {
            padding: 0;
            border-bottom: 0;
            background: transparent;
        }

        .admin-sms-page .admin-sms-toolbar .admin-primary-button,
        .admin-sms-page .admin-sms-toolbar .admin-clear-filter {
            margin-top: 0;
        }

        .admin-sms-page .admin-sms-toolbar .form-control {
            height: 38px;
            border-color: #d5e4f5;
            border-radius: 9px;
            color: #315786;
            font-size: 11px;
        }

        .admin-sms-page .admin-sms-toolbar .form-control:focus {
            border-color: #55a9ff;
            box-shadow: 0 0 0 3px rgba(85, 169, 255, .14);
        }

        /* Booked patients table: centre the selection column and tidy the checkboxes */
        .admin-sms-page .admin-sms-booked-table th:first-child,
        .admin-sms-page .admin-sms-booked-table td:first-child {
            width: 52px;
            text-align: center;
        }

        .admin-sms-page .admin-doctor-table input[type="checkbox"] {
            width: 16px;
            height: 16px;
            margin: 0;
            accent-color: #0877ed;
            cursor: pointer;
            vertical-align: middle;
        }

        /* Tables: no fixed-height internal scroll area, let the card grow naturally */
        .admin-sms-page .admin-doctor-table-wrap {
            overflow: visible !important;
            max-height: none !important;
            height: auto;
        }

        /* Booked patients: keep the stored contact number on one line */
        .admin-sms-page .admin-doctor-table .admin-sms-col-contact {
            white-space: nowrap;
        }

        /* Recently sent messages: spread the columns across the full card width.
           Recipient and Sent At keep their content on one line with a little
           breathing room, while Message absorbs the remaining space. */
        .admin-sms-page .admin-sms-history-table .admin-sms-col-patient {
            min-width: 180px;
            white-space: nowrap;
        }

        .admin-sms-page .admin-sms-history-table .admin-sms-col-recipient {
            min-width: 160px;
            white-space: nowrap;
        }

        .admin-sms-page .admin-sms-history-table .admin-sms-col-message {
            width: 100%;
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .admin-sms-page .admin-sms-history-table .admin-sms-col-sent {
            min-width: 200px;
            padding-left: 28px;
            padding-right: 24px;
            white-space: nowrap;
        }

        /* Pagination strip sits cleanly under the booked-patient table */
        .admin-sms-page .admin-doctor-pagination {
            background: #fbfdff;
        }

        /* Footer action bar inside the booked-patient card */
        .admin-sms-page .admin-sms-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            padding: 14px 20px;
            border-top: 1px solid #e4edf7;
            background: #fbfdff;
        }

        .admin-sms-page .admin-sms-actions .admin-primary-button {
            margin-top: 0;
        }

        .admin-sms-page .admin-sms-notice {
            display: inline-flex;
            align-items: center;
            color: #b4234a;
            font-size: 12px;
            font-weight: 600;
        }

        .admin-sms-page .admin-sms-notice[hidden] {
            display: none !important;
        }

        /* Send-message modal: match the add/view appointment and record modals */
        #smsMessageModal .modal-body {
            background: #f4f8ff;
        }

        #smsMessageModal .admin-panel {
            border: 1px solid #0877ed;
            background: #f4f8ff;
        }

        #smsMessageModal .admin-panel-title {
            padding: 12px 20px;
            background: #0877ed;
        }

        #smsMessageModal .admin-panel-title h3 {
            margin: 0;
            color: #ffffff;
            font-size: 15px;
            font-weight: 600;
        }

        #smsMessageModal .admin-panel-title i {
            color: #ffffff;
            font-size: 16px;
        }

        #smsMessageModal .admin-panel > .admin-muted-text {
            margin: 0;
            padding: 14px 20px 0;
        }

        #smsMessageModal .admin-panel > .form-field {
            padding: 12px 20px 18px;
        }

        #smsMessageModal .form-control {
            min-height: 40px;
            border-color: #d5e4f5;
            background-color: #ffffff;
            color: #0a326c;
            font-size: 15px;
        }

        #smsMessageModal .form-control:focus {
            border-color: #55a9ff;
            box-shadow: 0 0 0 3px rgba(85, 169, 255, .14);
        }

        @media (max-width: 767.98px) {
            .admin-sms-page .admin-sms-toolbar,
            .admin-sms-page .admin-sms-actions {
                padding: 12px 14px;
            }

            /* Mobile: the service dropdown and the two date fields sit too close
               together and read as one block. Give the dropdown and the first date
               their own bottom spacing so all three controls keep a clean,
               separate vertical row. */
            .admin-sms-page .admin-sms-toolbar .admin-doctor-filters > .form-select,
            .admin-sms-page .admin-sms-toolbar .admin-doctor-filters > input[type="date"]:not(:last-of-type) {
                margin-bottom: 14px;
            }

            .admin-sms-page .admin-sms-actions .admin-primary-button {
                width: 100%;
                justify-content: center;
            }

            /* Fix: the 7-column booked-patients table squeezed the empty-state
               placeholder text into a one-character-wide column on mobile.
               Give the table a minimum width so the empty-state <td colspan="7">
               has enough room, and let the wrapper scroll horizontally. */
            .admin-sms-page .admin-doctor-table-wrap {
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch;
            }

            .admin-sms-page .admin-sms-booked-table {
                min-width: 480px;
            }

            /* Make empty state horizontal on mobile for Booked Patients table */
            .admin-sms-page .admin-sms-booked-table .admin-doctor-empty {
                flex-direction: row;
                flex-wrap: wrap;
                min-height: auto;
                padding: 20px 16px;
                gap: 12px;
            }
            .admin-sms-page .admin-sms-booked-table .admin-doctor-empty i {
                margin-bottom: 0;
                font-size: 24px;
            }
            .admin-sms-page .admin-sms-booked-table .admin-doctor-empty > div,
            .admin-sms-page .admin-sms-booked-table .admin-doctor-empty > strong,
            .admin-sms-page .admin-sms-booked-table .admin-doctor-empty > span {
                flex: 1 1 auto;
            }
            .admin-sms-page .admin-sms-booked-table .admin-doctor-empty strong {
                font-size: 14px;
            }
            .admin-sms-page .admin-sms-booked-table .admin-doctor-empty span {
                font-size: 12px;
            }
        }

        /* Tablet: ensure table scrolls if needed */
        @media (max-width: 991.98px) {
            .admin-sms-page .admin-doctor-table-wrap {
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch;
            }
            .admin-sms-page .admin-sms-booked-table {
                min-width: 600px;
            }
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(function () {
            const form = document.getElementById('smsSendForm');
            const modalEl = document.getElementById('smsMessageModal');

            if (!form || !modalEl) {
                return;
            }

            const checkboxes = Array.from(form.querySelectorAll('.sms-row-checkbox'));
            const selectAll = document.getElementById('smsSelectAll');
            const sendButton = document.getElementById('smsSendSelected');
            const notice = document.getElementById('smsSelectionNotice');
            const countEl = document.getElementById('smsRecipientCount');
            const messageEl = document.getElementById('smsMessage');

            const selectedCount = () => checkboxes.filter((box) => box.checked).length;

            const refresh = () => {
                const count = selectedCount();

                if (countEl) {
                    countEl.textContent = String(count);
                }

                if (selectAll) {
                    selectAll.checked = count > 0 && count === checkboxes.length;
                    selectAll.indeterminate = count > 0 && count < checkboxes.length;
                }

                if (count > 0 && notice) {
                    notice.hidden = true;
                }
            };

            if (selectAll) {
                selectAll.addEventListener('change', function () {
                    checkboxes.forEach((box) => { box.checked = this.checked; });
                    refresh();
                });
            }

            checkboxes.forEach((box) => box.addEventListener('change', refresh));

            if (sendButton) {
                sendButton.addEventListener('click', function () {
                    if (selectedCount() === 0) {
                        if (notice) {
                            notice.hidden = false;
                        }

                        return;
                    }

                    refresh();
                    bootstrap.Modal.getOrCreateInstance(modalEl, { backdrop: true }).show();
                });
            }

            form.addEventListener('submit', function (event) {
                if (selectedCount() === 0) {
                    event.preventDefault();

                    if (notice) {
                        notice.hidden = false;
                    }

                    return;
                }

                if (!messageEl || messageEl.value.trim() === '') {
                    event.preventDefault();

                    if (messageEl) {
                        messageEl.focus();
                    }
                }
            });

            refresh();
        });
    </script>
@endpush
