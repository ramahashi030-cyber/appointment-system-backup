@extends('layouts.admin')

@section('title', 'Triagers')

@section('sidebar')
    @include('partials.admin-sidebar')
@endsection

@section('header')
    @include('partials.admin-header')
@endsection

@section('content')
    {{-- Remove table scrollbars: tables wrap to fit instead of scrolling --}}
    <style>
        .admin-doctor-table-wrap {
            overflow: visible !important;
            max-height: none !important;
        }

        .admin-doctor-table {
            width: 100%;
            min-width: 0 !important;
            table-layout: auto;
        }

        .admin-doctor-table th,
        .admin-doctor-table td {
            white-space: normal;
            overflow-wrap: anywhere;
        }
    </style>
    <div class="admin-dashboard-content admin-doctor-content admin-triagers-page">
        <section class="admin-telemedicine-banner admin-doctor-banner" aria-labelledby="triagerDirectoryTitle">
            <div class="admin-telemedicine-glow" aria-hidden="true"></div>
            <div class="admin-telemedicine-content">
                <div class="admin-telemedicine-mark" aria-hidden="true">
                    <i class="bi bi-clipboard2-pulse-fill"></i>
                </div>
                <div class="admin-telemedicine-copy">
                    <h1 id="triagerDirectoryTitle">Triager Accounts</h1>
                    <p class="admin-telemedicine-welcome">Triage Staff Management</p>
                    <p class="admin-telemedicine-description">Create and manage triager accounts who process patient appointment requests.</p>
                </div>
            </div>
        </section>

        <div class="admin-doctor-stat-grid">
            <article class="admin-doctor-stat-card">
                <span class="admin-doctor-stat-icon blue"><i class="bi bi-clipboard2-pulse-fill" aria-hidden="true"></i></span>
                <span><small>Total triagers</small><strong>{{ number_format($triagers->count()) }}</strong></span>
            </article>
            <button class="admin-doctor-add-button admin-doctor-stat-action" type="button" data-bs-toggle="modal" data-bs-target="#addTriagerModal">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                <span>Add triager</span>
            </button>
        </div>

        <section class="admin-panel admin-doctor-roster-panel" aria-labelledby="triagerRosterTitle">
            <header class="admin-panel-header admin-doctor-roster-header">
                <div class="admin-panel-title">
                    <i class="bi bi-clipboard2-pulse-fill" aria-hidden="true"></i>
                    <h2 id="triagerRosterTitle">Triager roster</h2>
                </div>
                <span class="admin-muted-text">{{ $triagers->count() }} triager{{ $triagers->count() === 1 ? '' : 's' }}</span>
            </header>

            <div class="admin-doctor-table-wrap" data-doctor-table-wrap>
                <table class="admin-doctor-table">
                    <caption class="visually-hidden">Triager accounts</caption>
                    <thead>
                        <tr>
                            <th scope="col">Name</th>
                            <th scope="col">Username</th>
                            <th scope="col">Email</th>
                            <th scope="col">Contact</th>
                            <th scope="col"><span class="visually-hidden">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($triagers as $triager)
                            <tr>
                                <td>
                                    <div class="admin-doctor-person">
                                        <span class="admin-avatar">{{ strtoupper(substr((string) ($triager->firstname ?: 'T'), 0, 1).substr((string) ($triager->lastname ?: 'S'), 0, 1)) }}</span>
                                        <span>
                                            <strong>{{ trim($triager->firstname.' '.$triager->lastname) ?: 'Unnamed triager' }}</strong>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="admin-doctor-primary-text">{{ $triager->username ?: '—' }}</span>
                                </td>
                                <td>
                                    <span class="admin-doctor-primary-text">{{ $triager->email ?: '—' }}</span>
                                </td>
                                <td>
                                    <span class="admin-doctor-primary-text">{{ $triager->contact_no ?: '—' }}</span>
                                </td>
                                <td>
                                    <div class="admin-doctor-actions">
                                        <a href="{{ route('admin.triagers', ['edit' => $triager->id]) }}" data-bs-toggle="modal" data-bs-target="#editTriagerModal" data-edit-id="{{ $triager->id }}" data-edit-firstname="{{ $triager->firstname }}" data-edit-lastname="{{ $triager->lastname }}" data-edit-username="{{ $triager->username }}" data-edit-email="{{ $triager->email }}" data-edit-contact="{{ $triager->contact_no }}">Edit</a>
                                        <form method="POST" action="{{ route('admin.triagers.destroy', $triager) }}" onsubmit="return confirm('Delete this triager account?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="admin-doctor-empty">
                                        <i class="bi bi-person-x" aria-hidden="true"></i>
                                        <strong>No triager accounts found</strong>
                                        <span>Create a triager account to start processing appointment requests.</span>
                                        <a href="#" data-bs-toggle="modal" data-bs-target="#addTriagerModal">Add triager</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    {{-- ADD TRIAGER MODAL --}}
    <div class="modal fade admin-doctor-modal" id="addTriagerModal" tabindex="-1" aria-labelledby="addTriagerModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content">
                <header class="modal-header admin-doctor-modal-header">
                    <div class="admin-telemedicine-glow" aria-hidden="true"></div>
                    <div class="admin-telemedicine-content">
                        <div class="admin-telemedicine-mark" aria-hidden="true">
                            <i class="bi bi-person-plus-fill"></i>
                        </div>
                        <div class="admin-telemedicine-copy">
                            <h2 class="modal-title" id="addTriagerModalTitle">Add triager</h2>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </header>
                <div class="modal-body">
                    <form method="POST" action="{{ route('admin.triagers.store') }}">
                        @csrf
                        <div class="admin-panel">
                            <div class="admin-panel-title">
                                <i class="bi bi-person-fill" aria-hidden="true"></i>
                                <h3>Account information</h3>
                            </div>
                            <div class="admin-doctor-form">
                                <div class="form-field">
                                    <label for="triagerFirstname">First name <span style="color: #8ca2bd; font-weight: 400;">*</span></label>
                                    <input type="text" class="form-control" id="triagerFirstname" name="firstname" value="{{ old('firstname') }}" required maxlength="100">
                                    @error('firstname') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="form-field">
                                    <label for="triagerLastname">Last name <span style="color: #8ca2bd; font-weight: 400;">*</span></label>
                                    <input type="text" class="form-control" id="triagerLastname" name="lastname" value="{{ old('lastname') }}" required maxlength="100">
                                    @error('lastname') <div class="invalid-feedback">{{ $message }}</div> @endif
                                </div>
                                <div class="form-field">
                                    <label for="triagerUsername">Username <span style="color: #8ca2bd; font-weight: 400;">*</span></label>
                                    <input type="text" class="form-control" id="triagerUsername" name="username" value="{{ old('username') }}" required maxlength="100">
                                    @error('username') <div class="invalid-feedback">{{ $message }}</div> @endif
                                </div>
                                <div class="form-field">
                                    <label for="triagerEmail">Email <span style="color: #8ca2bd; font-weight: 400;">*</span></label>
                                    <input type="email" class="form-control" id="triagerEmail" name="email" value="{{ old('email') }}" required maxlength="191">
                                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @endif
                                </div>
                                <div class="form-field">
                                    <label for="triagerContact">Contact number</label>
                                    <input type="text" class="form-control" id="triagerContact" name="contact_no" value="{{ old('contact_no') }}" maxlength="20">
                                    @error('contact_no') <div class="invalid-feedback">{{ $message }}</div> @endif
                                </div>
                                <div class="form-field">
                                    <label for="triagerPassword">Password <span style="color: #8ca2bd; font-weight: 400;">*</span></label>
                                    <input type="password" class="form-control" id="triagerPassword" name="password" required minlength="8" maxlength="100">
                                    <small style="color: #8ca2bd; font-size: 12px;">Minimum 8 characters</small>
                                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @endif
                                </div>
                            </div>
                        </div>
                        <div class="admin-doctor-form-actions">
                            <button type="submit" class="admin-primary-button">
                                <i class="bi bi-check-lg" aria-hidden="true"></i>
                                <span>Create triager</span>
                            </button>
                            <button type="button" class="admin-secondary-button" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- EDIT TRIAGER MODAL --}}
    <div class="modal fade admin-doctor-modal" id="editTriagerModal" tabindex="-1" aria-labelledby="editTriagerModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <header class="modal-header admin-doctor-modal-header">
                    <div class="admin-telemedicine-glow" aria-hidden="true"></div>
                    <div class="admin-telemedicine-content">
                        <div class="admin-telemedicine-mark" aria-hidden="true">
                            <i class="bi bi-pencil-fill"></i>
                        </div>
                        <div class="admin-telemedicine-copy">
                            <h2 class="modal-title" id="editTriagerModalTitle">Edit triager</h2>
                            <p class="admin-telemedicine-description">Update triager account information.</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </header>
                <div class="modal-body">
                    <form method="POST" id="editTriagerForm" action="">
                        @csrf
                        @method('PUT')
                        <div class="admin-panel">
                            <div class="admin-panel-title">
                                <i class="bi bi-person-fill" aria-hidden="true"></i>
                                <h3>Account information</h3>
                            </div>
                            <div class="admin-doctor-form">
                                <div class="form-field">
                                    <label for="editTriagerFirstname">First name <span style="color: #8ca2bd; font-weight: 400;">*</span></label>
                                    <input type="text" class="form-control" id="editTriagerFirstname" name="firstname" required maxlength="100">
                                    @error('firstname') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="form-field">
                                    <label for="editTriagerLastname">Last name <span style="color: #8ca2bd; font-weight: 400;">*</span></label>
                                    <input type="text" class="form-control" id="editTriagerLastname" name="lastname" required maxlength="100">
                                    @error('lastname') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="form-field">
                                    <label for="editTriagerUsername">Username <span style="color: #8ca2bd; font-weight: 400;">*</span></label>
                                    <input type="text" class="form-control" id="editTriagerUsername" name="username" required maxlength="100">
                                    @error('username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="form-field">
                                    <label for="editTriagerEmail">Email <span style="color: #8ca2bd; font-weight: 400;">*</span></label>
                                    <input type="email" class="form-control" id="editTriagerEmail" name="email" required maxlength="191">
                                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="form-field">
                                    <label for="editTriagerContact">Contact number</label>
                                    <input type="text" class="form-control" id="editTriagerContact" name="contact_no" maxlength="20">
                                    @error('contact_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="form-field">
                                    <label for="editTriagerPassword">New password (leave blank to keep current)</label>
                                    <input type="password" class="form-control" id="editTriagerPassword" name="password" minlength="8" maxlength="100">
                                    @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                        </div>
                        <div class="admin-doctor-form-actions">
                            <button type="submit" class="admin-primary-button">
                                <i class="bi bi-check-lg" aria-hidden="true"></i>
                                <span>Save changes</span>
                            </button>
                            <button type="button" class="admin-secondary-button" data-bs-dismiss="modal">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const editModal = document.getElementById('editTriagerModal');
            const editForm = document.getElementById('editTriagerForm');

            editModal.addEventListener('show.bs.modal', (event) => {
                const trigger = event.relatedTarget;
                if (!trigger) return;

                const id = trigger.dataset.editId;
                const firstname = trigger.dataset.editFirstname || '';
                const lastname = trigger.dataset.editLastname || '';
                const username = trigger.dataset.editUsername || '';
                const email = trigger.dataset.editEmail || '';
                const contact = trigger.dataset.editContact || '';

                editForm.action = '{{ route('admin.triagers.update', ['admin' => '__ID__']) }}'.replace('__ID__', id);
                document.getElementById('editTriagerFirstname').value = firstname;
                document.getElementById('editTriagerLastname').value = lastname;
                document.getElementById('editTriagerUsername').value = username;
                document.getElementById('editTriagerEmail').value = email;
                document.getElementById('editTriagerContact').value = contact;
                document.getElementById('editTriagerPassword').value = '';
            });

            @if ($createMode)
                new bootstrap.Modal(document.getElementById('addTriagerModal')).show();
            @endif
        });
    </script>
@endpush