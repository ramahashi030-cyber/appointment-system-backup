@extends('layouts.admin')

@section('title', 'Triager Dashboard')

@section('sidebar')
    @include('partials.triager-sidebar')
@endsection

@section('header')
    @include('partials.admin-header')
@endsection

@section('content')
    <div class="admin-dashboard-content admin-doctor-content triager-dashboard-content">
        {{-- Triager Dashboard Content --}}
        <div class="triager-dashboard-header">
            <h1>Triager Dashboard</h1>
            <form class="admin-mobile-logout" action="{{ route('admin.logout') }}" method="POST">
                @csrf
                <button type="submit">
                    <i class="bi bi-box-arrow-right" aria-hidden="true"></i>
                    Logout
                </button>
            </form>
        </div>

        {{-- Rest of dashboard content would go here --}}
    </div>
@endsection

@push('scripts')
    <script>
    (function () {
        var oldModal = document.getElementById('adminLogoutModal');
        var newModal = document.getElementById('triagerLogoutConfirmation');

        if (oldModal) {
            // Hide the old shared modal
            oldModal.style.display = 'none';
            oldModal.remove();
        }

        // Initialize the new confirmation modal
        if (newModal) {
            var cancelBtn = newModal.querySelector('[data-logout-cancel]');
            var confirmBtn = newModal.querySelector('[data-logout-confirm]');

            if (cancelBtn) {
                cancelBtn.addEventListener('click', function () {
                    var modal = bootstrap.Modal.getInstance(newModal);
                    if (modal) modal.hide();
                });
            }

            // Prevent Escape from closing the modal
            newModal.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    event.preventDefault();
                }
            });

            // Ensure the confirm button submits the form properly
            if (confirmBtn) {
                confirmBtn.addEventListener('click', function (e) {
                    // Let the form submit naturally
                });
            }
        }
    })();
    </script>
@endpush

{{-- Triager Logout Confirmation Flashcard --}}
<div class="modal fade" id="triagerLogoutConfirmation" tabindex="-1" aria-labelledby="triagerLogoutConfirmationTitle" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content triager-logout-flashcard">
            <div class="triager-logout-flashcard-header">
                <div class="triager-logout-flashcard-icon" aria-hidden="true">
                    <i class="bi bi-box-arrow-right"></i>
                </div>
            </div>
            <div class="triager-logout-flashcard-body">
                <h2 class="triager-logout-flashcard-title" id="triagerLogoutConfirmationTitle">Logout Confirmation</h2>
                <p class="triager-logout-flashcard-message">Are you sure you want to log out?</p>
            </div>
            <div class="triager-logout-flashcard-actions">
                <button type="button" class="btn triager-logout-flashcard-btn triager-logout-flashcard-btn--cancel" data-bs-dismiss="modal" data-logout-cancel>
                    <i class="bi bi-x-lg me-2" aria-hidden="true"></i>Cancel
                </button>
                <form action="{{ route('admin.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn triager-logout-flashcard-btn triager-logout-flashcard-btn--confirm" data-logout-confirm>
                        <i class="bi bi-box-arrow-right me-2" aria-hidden="true"></i>Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Triager Logout Confirmation Flashcard Styles --}}
@push('styles')
    <style>
        /* =============================================================
           Triager Logout Confirmation Flashcard
           Clean, modern design consistent with triager dashboard visual language.
           Uses the triager teal/cyan accent color from the dashboard.
           ============================================================= */

        .triager-logout-flashcard {
            border: none;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(13, 40, 90, 0.25), 0 8px 16px rgba(13, 40, 90, 0.15);
            background: #fff;
            overflow: hidden;
        }

        .triager-logout-flashcard .modal-dialog {
            max-width: 360px;
            margin: 1.75rem auto;
        }

        .triager-logout-flashcard-header {
            padding: 28px 24px 16px;
            background: linear-gradient(135deg, #0b9e9e 0%, #087c7c 100%);
            text-align: center;
        }

        .triager-logout-flashcard-icon {
            width: 56px;
            height: 56px;
            margin: 0 auto;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .triager-logout-flashcard-icon i {
            font-size: 24px;
            color: #fff;
            line-height: 1;
        }

        .triager-logout-flashcard-body {
            padding: 24px 28px 20px;
            text-align: center;
        }

        .triager-logout-flashcard-title {
            margin: 0 0 10px;
            font-size: 18px;
            font-weight: 700;
            color: #0a326c;
            letter-spacing: -0.01em;
        }

        .triager-logout-flashcard-message {
            margin: 0;
            font-size: 14px;
            color: #5a7ba6;
            line-height: 1.55;
        }

        .triager-logout-flashcard-actions {
            display: flex;
            gap: 10px;
            padding: 0 24px 24px;
            justify-content: center;
        }

        .triager-logout-flashcard-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            min-width: 110px;
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 600;
            line-height: 1.4;
            transition: all 0.15s ease;
        }

        .triager-logout-flashcard-btn i {
            font-size: 14px;
            line-height: 1;
        }

        .triager-logout-flashcard-btn--cancel {
            background: #f0f4f8;
            color: #4a658a;
            border: 1px solid #d6e2ee;
        }

        .triager-logout-flashcard-btn--cancel:hover {
            background: #e4ebf3;
            color: #3d5878;
            border-color: #c8d7e8;
            transform: translateY(-1px);
        }

        .triager-logout-flashcard-btn--cancel:active {
            transform: translateY(0);
        }

        .triager-logout-flashcard-btn--confirm {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: #fff;
            border: none;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        .triager-logout-flashcard-btn--confirm:hover {
            background: linear-gradient(135deg, #c82333 0%, #bd2130 100%);
            box-shadow: 0 6px 16px rgba(220, 53, 69, 0.35);
            transform: translateY(-1px);
        }

        .triager-logout-flashcard-btn--confirm:active {
            transform: translateY(0);
        }

        /* Mobile adjustments */
        @media (max-width: 480px) {
            .triager-logout-flashcard .modal-dialog {
                margin: 1rem auto;
                max-width: calc(100% - 2rem);
            }

            .triager-logout-flashcard-header {
                padding: 24px 20px 12px;
            }

            .triager-logout-flashcard-icon {
                width: 48px;
                height: 48px;
            }

            .triager-logout-flashcard-icon i {
                font-size: 20px;
            }

            .triager-logout-flashcard-body {
                padding: 20px 20px 16px;
            }

            .triager-logout-flashcard-title {
                font-size: 17px;
            }

            .triager-logout-flashcard-message {
                font-size: 13.5px;
            }

            .triager-logout-flashcard-actions {
                flex-direction: column;
                gap: 8px;
                padding: 0 20px 20px;
            }

            .triager-logout-flashcard-btn {
                width: 100%;
                min-width: 0;
                padding: 12px 20px;
            }
        }

        /* Reduced motion support */
        @media (prefers-reduced-motion: reduce) {
            .triager-logout-flashcard-btn {
                transition: none;
            }
        }
    </style>
@endpush