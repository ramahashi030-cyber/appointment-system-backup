{{-- Dashboard shortcut, consent, and appointment-state modals. --}}
<div class="modal fade dashboard-modal dashboard-consent-modal" id="careConsentModal" tabindex="-1" aria-labelledby="careConsentModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content dashboard-modal-content">
            <form action="{{ route('telemed.consent') }}" method="POST" data-consent-form>
                @csrf
                <input type="hidden" name="service_id" value="" data-consent-service-id>
                <input type="hidden" name="service_mode" value="TELE" data-consent-service-mode>

                <header class="dashboard-modal-header dashboard-consent-header">
                    <div class="dashboard-modal-title-group">
                        <span class="dashboard-modal-title-icon" aria-hidden="true"><i class="bi bi-shield-check"></i></span>
                        <span>
                            <h2 id="careConsentModalTitle">Consent for Care and Data Processing</h2>
                            <small>Pahintulot para sa Gamutan at Paggamit ng Datos</small>
                        </span>
                    </div>
                    <button type="button" class="dashboard-modal-close" data-bs-dismiss="modal" aria-label="Close consent modal">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </header>

                <div class="modal-body dashboard-modal-body dashboard-consent-body">
                    <div class="consent-assurance">
                        <i class="bi bi-lock-fill" aria-hidden="true"></i>
                        <div>
                            <strong>Your privacy and informed choice matter.</strong>
                            <span>Please read each section carefully before continuing. The selected service is recorded only after you choose “I AGREE.”</span>
                        </div>
                    </div>

                    <div class="consent-selected-service" data-consent-service-label hidden>
                        <i class="bi bi-calendar2-check" aria-hidden="true"></i>
                        Booking: <strong data-consent-service-name>Telemedicine consultation</strong>
                        <span class="modal-service-mode tele" data-consent-service-mode-label>
                            <i class="bi bi-camera-video-fill" aria-hidden="true"></i>
                            <span data-consent-service-mode-text>Telemedicine</span>
                        </span>
                    </div>

                    <section class="consent-section" aria-labelledby="consentPrivacyTitle">
                        <div class="consent-section-heading">
                            <span>I</span>
                            <div>
                                <h3 id="consentPrivacyTitle">Data Privacy Statement</h3>
                                <p>Pahayag sa Privacy ng Datos</p>
                            </div>
                        </div>
                        <p>
                            In compliance with the Data Privacy Act of 2012 (Republic Act No. 10173), I hereby authorize the Department of Family and Community Medicine to collect, process, store, and share my personal and sensitive health information. I understand that my data will be handled with strict confidentiality and used solely for:
                        </p>
                        <ul class="consent-purpose-list">
                            <li>Patient registration, triaging, and scheduling of appointments.</li>
                            <li>Clinical assessment via face-to-face or telemedicine platforms.</li>
                            <li>Coordination of care within the Health Care Provider Network (HCPN) and referral systems.</li>
                            <li>Mandatory PhilHealth Konsulta reporting and DOH health information requirements.</li>
                        </ul>
                        <p class="consent-translation" lang="fil">
                            Alinsunod sa Data Privacy Act of 2012 (RA 10173), pinahihintulutan ko ang Department of Family and Community Medicine na kolektahin at gamitin ang aking impormasyon. Nauunawaan ko na ang aking datos ay ituturing na kumpidensyal at gagamitin lamang para sa aking gamutan, appointment, at mga ulat sa PhilHealth at DOH.
                        </p>
                    </section>

                    <section class="consent-section" aria-labelledby="consentConsultationTitle">
                        <div class="consent-section-heading">
                            <span>II</span>
                            <div>
                                <h3 id="consentConsultationTitle">Telemedicine &amp; Face-to-Face Consultation</h3>
                                <p>Telemedicine at pisikal na pagpapakonsulta</p>
                            </div>
                        </div>
                        <div class="consent-care-types">
                            <div>
                                <i class="bi bi-camera-video-fill" aria-hidden="true"></i>
                                <p><strong>Telemedicine:</strong> I understand that telemedicine is the default entry point for non-urgent care. I acknowledge its limitations and agree that the physician may require an in-person follow-up if my condition necessitates a physical exam.</p>
                            </div>
                            <div>
                                <i class="bi bi-person-check-fill" aria-hidden="true"></i> 
                                <p><strong>In-Person:</strong> I consent to physical examinations and medical procedures deemed necessary by the attending physician during my scheduled clinic visit.</p>
                            </div>
                        </div>
                        <p class="consent-translation" lang="fil">
                            Nauunawaan ko na ang telemedicine ang unang hakbang para sa mga simpleng karamdaman. Sumasang-ayon din ako sa pisikal na pagsusuri sa clinic kung ito ay kakailanganin ng aking doktor.
                        </p>
                    </section>

                    <section class="consent-section" aria-labelledby="consentRightsTitle">
                        <div class="consent-section-heading">
                            <span>III</span>
                            <div>
                                <h3 id="consentRightsTitle">Patient Rights</h3>
                                <p>Mga Karapatan ng Pasyente</p>
                            </div>
                        </div>
                        <p>
                            I am aware of my rights under the Data Privacy Act, including the right to access, correct, or request the removal of my data, and the right to withdraw this consent at any time, subject to legal and medical record retention regulations.
                        </p>
                        <p class="consent-translation" lang="fil">
                            Batid ko ang aking mga karapatan sa ilalim ng Data Privacy Act, kabilang ang karapatang makita, itama, o bawiin ang aking pahintulot sa anumang oras, ayon sa itinakda ng batas.
                        </p>
                    </section>

                    <section class="consent-declaration" aria-labelledby="consentDeclarationTitle">
                        <div class="consent-declaration-icon" aria-hidden="true"><i class="bi bi-patch-check-fill"></i></div>
                        <div>
                            <h3 id="consentDeclarationTitle">Declaration (Pagpapatunay)</h3>
                            <p>By clicking <strong>“I AGREE”</strong>, I certify that I have read and understood the terms above and voluntarily provide my consent.</p>
                            <p lang="fil">Sa pag-click ng <strong>“I AGREE”</strong>, pinapatunayan ko na nabasa at naintindihan ko ang mga nakasaad dito at kusang-loob akong sumasang-ayon.</p>
                        </div>
                    </section>

                    <div class="booking-form-alert" data-consent-error hidden role="alert"></div>
                </div>

                <footer class="dashboard-modal-footer">
                    <button type="button" class="dashboard-modal-button secondary" data-bs-dismiss="modal">Cancel</button>
                    @if ($patient !== null)
                        <button type="submit" class="dashboard-modal-button primary" data-consent-submit>
                            <i class="bi bi-check2-circle" aria-hidden="true"></i>
                            <span>I AGREE &amp; CONTINUE</span>
                        </button>
                    @else
                        <a class="dashboard-modal-button primary" href="{{ route('auth.login') }}">
                            <i class="bi bi-box-arrow-in-right" aria-hidden="true"></i>
                            Sign in to continue
                        </a>
                    @endif
                </footer>
            </form>
        </div>
    </div>
</div>

@include('partials.active-appointment-modal')
@include('partials.appointment-cancel-modal')

<div class="modal fade dashboard-modal" id="myVisitsModal" tabindex="-1" aria-labelledby="myVisitsModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content dashboard-modal-content">
            <header class="dashboard-modal-header">
                <div class="dashboard-modal-title-group">
                    <span class="dashboard-modal-title-icon purple" aria-hidden="true"><i class="bi bi-calendar2-check-fill"></i></span>
                    <span>
                        <h2 id="myVisitsModalTitle">My Visits</h2>
                        <small>Review your upcoming telemedicine appointments</small>
                    </span>
                </div>
                <button type="button" class="dashboard-modal-close" data-bs-dismiss="modal" aria-label="Close visits modal">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </header>

            <div class="modal-body dashboard-modal-body">
                @if (! empty($pendingFaceRequests))
                    <div class="modal-section-label">
                        <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                        Pending Face-to-Face Requests
                    </div>
                    @foreach ($pendingFaceRequests as $pending)
                        <article class="modal-visit-row modal-pending-row">
                            <span class="modal-visit-date" aria-hidden="true"><i class="bi bi-hospital"></i></span>
                            <div class="modal-visit-copy">
                                <strong>{{ $pending['consultation_details'] ?? 'Face-to-Face Consultation' }}</strong>
                                <span><i class="bi bi-clock" aria-hidden="true"></i> {{ $pending['requested_at']?->format('M j, Y h:i A') ?? '—' }}</span>
                            </div>
                            <span class="dashboard-status pending">Waiting for triage</span>
                        </article>
                    @endforeach
                @endif

                @if (! empty($upcoming))
                    @if (! empty($pendingFaceRequests))
                        <div class="modal-section-label">
                            <i class="bi bi-calendar2-check" aria-hidden="true"></i>
                            Scheduled Visits
                        </div>
                    @endif
                    @foreach ($upcoming as $visit)
                        @php
                            $visitIsCancelled = str_contains(strtolower((string) ($visit['status'] ?? '')), 'cancel');
                            $visitStatus = (string) ($visit['status'] ?? 'Booked');
                            $visitService = (string) ($visit['service_name'] ?? 'Consultation');
                            $visitDate = (string) ($visit['date'] ?? '—');
                            $visitTime = (string) ($visit['time_slot'] ?? '—');
                            $visitLink = $visit['meeting_link'] ?? null;
                            $visitQrUrl = $visit['qr_code_url'] ?? null;
                            $visitMode = $visit['mode'] ?? 'TELE';
                        @endphp
                        <article class="modal-visit-row">
                            <span class="modal-visit-date" aria-hidden="true"><i class="bi {{ $visitMode === 'FACE' ? 'bi-hospital' : 'bi-camera-video-fill' }}"></i></span>
                            <div class="modal-visit-copy">
                                <strong>{{ $visitService }}</strong>
                                <span><i class="bi bi-calendar3" aria-hidden="true"></i> {{ $visitDate }} <b>·</b> <i class="bi bi-clock" aria-hidden="true"></i> {{ $visitTime }}</span>
                            </div>
                            <span class="dashboard-status {{ $visitIsCancelled ? 'cancelled' : 'booked' }}">{{ $visitStatus }}</span>
                            <div class="modal-visit-action">
                                @if ($visitIsCancelled)
                                    <button type="button" class="dashboard-join-button cancelled"
                                            data-open-cancelled-appointment
                                            data-cancelled-service="{{ $visitService }}"
                                            data-cancelled-date="{{ $visitDate }}"
                                            data-cancelled-time="{{ $visitTime }}">
                                        <i class="bi bi-camera-video-fill" aria-hidden="true"></i> Join
                                    </button>
                                @elseif ($visitMode === 'FACE' && $visitQrUrl)
                                    <button type="button" class="dashboard-join-button"
                                            data-qr-expand="#bookingQrEnlargeModal"
                                            data-qr-src="{{ $visitQrUrl }}"
                                            data-qr-alt="Appointment QR code">
                                        <i class="bi bi-qr-code" aria-hidden="true"></i> QR
                                    </button>
                                @elseif (!empty($visitLink))
                                    <a class="dashboard-join-button" href="{{ $visitLink }}" target="_blank" rel="noopener">
                                        <i class="bi bi-camera-video-fill" aria-hidden="true"></i> Join
                                    </a>
                                @else
                                    <span class="modal-action-pending">Link pending</span>
                                @endif
                            </div>
                        </article>
                    @endforeach
                @endif

                @if (empty($upcoming) && empty($pendingFaceRequests))
                    <div class="modal-empty-state">
                        <i class="bi bi-calendar2-x" aria-hidden="true"></i>
                        <strong>No upcoming visits yet</strong>
                        <span>Your booked appointments and pending requests will appear here.</span>
                    </div>
                @endif
            </div>

            <footer class="dashboard-modal-footer">
                <button type="button" class="dashboard-modal-button secondary" data-bs-dismiss="modal">Close</button>
                <a class="dashboard-modal-button primary" href="{{ route('telemed.mine') }}">
                    View complete visit history <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </a>
            </footer>
        </div>
    </div>
</div>

<div class="modal fade dashboard-modal" id="servicesModal" tabindex="-1" aria-labelledby="servicesModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content dashboard-modal-content">
            <header class="dashboard-modal-header">
                <div class="dashboard-modal-title-group">
                    <span class="dashboard-modal-title-icon green" aria-hidden="true"><i class="bi bi-heart-pulse-fill"></i></span>
                    <span>
                        <h2 id="servicesModalTitle">Services</h2>
                        <small>Choose face-to-face or telemedicine services</small>
                    </span>
                </div>
                <button type="button" class="dashboard-modal-close" data-bs-dismiss="modal" aria-label="Close services modal">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </header>

            <div class="modal-body dashboard-modal-body">
                @if (empty($services))
                    <div class="modal-empty-state">
                        <i class="bi bi-hospital" aria-hidden="true"></i>
                        <strong>No services are available yet</strong>
                        <span>Please check again later for updated face-to-face and telemedicine services.</span>
                    </div>
                @else
                    <div class="modal-service-list">
                        @foreach ($services as $service)
                            @php
                                $serviceName = (string) ($service['service_name'] ?? 'Service');
                                $serviceDays = (string) ($service['availability_days'] ?? '');
                                $serviceMode = strtoupper((string) ($service['mode'] ?? 'TELE'));
                                $serviceModeLabel = (string) ($service['mode_label'] ?? 'Telemedicine');
                                $lowerServiceName = \Illuminate\Support\Str::lower($serviceName);
                                $serviceIcon = $serviceMode === 'FACE'
                                    ? 'bi-hospital-fill'
                                    : (\Illuminate\Support\Str::contains($lowerServiceName, ['family', 'medical']) ? 'bi-people-fill' : 'bi-camera-video-fill');
                            @endphp
                            <button type="button" class="modal-service-option"
                                    data-open-consent
                                    data-service-id="{{ $service['id'] ?? '' }}"
                                    data-service-mode="{{ $serviceMode }}"
                                    data-service-name="{{ $serviceName }}">
                                <span class="modal-service-option-icon" aria-hidden="true"><i class="bi {{ $serviceIcon }}"></i></span>
                                <span>
                                    <strong>{{ $serviceName }}</strong>
                                    <small><i class="bi bi-calendar3" aria-hidden="true"></i> {{ $serviceDays ?: 'Ask for schedule' }}</small>
                                    <span class="modal-service-mode {{ $serviceMode === 'FACE' ? 'face' : 'tele' }}">
                                        <i class="bi {{ $serviceMode === 'FACE' ? 'bi-person-check-fill' : 'bi-camera-video-fill' }}" aria-hidden="true"></i>
                                        {{ $serviceModeLabel }}
                                    </span>
                                </span>
                                <i class="bi bi-chevron-right" aria-hidden="true"></i>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>

            <footer class="dashboard-modal-footer">
                <span class="dashboard-modal-footer-note"><i class="bi bi-info-circle" aria-hidden="true"></i> Consent is requested before every new booking.</span>
                <button type="button" class="dashboard-modal-button secondary" data-bs-dismiss="modal">Close</button>
            </footer>
        </div>
    </div>
</div>

{{-- Records chooser: each option opens the shared page modal (#patientPageModal) at the bottom of this file. --}}
<div class="modal fade dashboard-modal" id="recordsModal" tabindex="-1" aria-labelledby="recordsModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content dashboard-modal-content">
            <header class="dashboard-modal-header">
                <div class="dashboard-modal-title-group">
                    <span class="dashboard-modal-title-icon navy" aria-hidden="true"><i class="bi bi-file-earmark-text-fill"></i></span>
                    <span>
                        <h2 id="recordsModalTitle">Health Records</h2>
                        <small>Access your personal medical information</small>
                    </span>
                </div>
                <button type="button" class="dashboard-modal-close" data-bs-dismiss="modal" aria-label="Close records modal">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </header>

            <div class="modal-body dashboard-modal-body">
                <div class="modal-record-grid">
                    <a href="{{ route('records.index') }}" class="modal-record-option"
                       data-patient-modal data-title="Medical Records">
                        <span><i class="bi bi-folder2-open" aria-hidden="true"></i></span>
                        <strong>Transaction Records</strong>
                        <small>View your transaction record entries</small>
                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </a>
                    <a href="{{ route('patient.prescriptions') }}" class="modal-record-option"
                       data-patient-modal data-title="Prescriptions">
                        <span class="purple"><i class="bi bi-capsule-pill" aria-hidden="true"></i></span>
                        <strong>Prescriptions</strong>
                        <small>Review prescribed medicines</small>
                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </a>
                    <a href="{{ route('patient.procedures') }}" class="modal-record-option"
                       data-patient-modal data-title="Procedures">
                        <span class="green"><i class="bi bi-activity" aria-hidden="true"></i></span>
                        <strong>Procedures</strong>
                        <small>See your procedure history</small>
                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </a>
                    <a href="{{ route('patient.profile') }}" class="modal-record-option"
                       data-patient-modal data-title="My Profile">
                        <span class="orange"><i class="bi bi-person-circle" aria-hidden="true"></i></span>
                        <strong>Profile</strong>
                        <small>Manage your personal details</small>
                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                    </a>
                </div>
            </div>

            <footer class="dashboard-modal-footer">
                <span class="dashboard-modal-footer-note"><i class="bi bi-shield-lock" aria-hidden="true"></i> Your health information is accessed securely.</span>
                <button type="button" class="dashboard-modal-button secondary" data-bs-dismiss="modal">Close</button>
            </footer>
        </div>
    </div>
</div>

{{--
    Real-time notification detail modal. Same id / close button / footer actions
    as before, but the list is now fed by the broadcast notification system and
    refreshes live while the modal is open. Opening it does NOT mark anything as
    read.
--}}
@include('partials.notification-modal', ['rtVariant' => 'patient', 'rtModalId' => 'notificationsModal'])

<div class="modal fade dashboard-modal" id="profileModal" tabindex="-1" aria-labelledby="profileModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content dashboard-modal-content">
            <header class="dashboard-modal-header">
                <div class="dashboard-modal-title-group">
                    <span class="dashboard-modal-title-icon navy" aria-hidden="true"><i class="bi bi-person-fill"></i></span>
                    <span>
                        <h2 id="profileModalTitle">Patient Profile</h2>
                        <small>Your personal and account information</small>
                    </span>
                </div>
                <button type="button" class="dashboard-modal-close" data-bs-dismiss="modal" aria-label="Close profile modal">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </header>

            <div class="modal-body dashboard-modal-body">
                @if ($patient === null)
                    <div class="modal-empty-state">
                        <i class="bi bi-person-lock" aria-hidden="true"></i>
                        <strong>Sign in to view your profile</strong>
                        <span>Your patient information is only available after signing in.</span>
                    </div>
                @else
                    <div class="profile-summary-card">
                        @if ($patient->profile_pic)
                            <img src="{{ \Illuminate\Support\Str::startsWith($patient->profile_pic, ['http://', 'https://']) ? $patient->profile_pic : asset($patient->profile_pic) }}" alt="{{ $patientName }}">
                        @else
                            <span><i class="bi bi-person-fill" aria-hidden="true"></i></span>
                        @endif
                        <div>
                            <strong>{{ $patientName }}</strong>
                            <small>{{ $patient->hospital_number ?: 'Patient account' }}</small>
                        </div>
                        <i class="bi bi-patch-check-fill" aria-label="Verified patient account"></i>
                    </div>

                    <div class="profile-detail-grid">
                        <div><i class="bi bi-calendar3" aria-hidden="true"></i><span><small>Date of Birth</small><strong>{{ $patient->dob?->format('M d, Y') ?: 'Not provided' }}</strong></span></div>
                        <div><i class="bi bi-gender-ambiguous" aria-hidden="true"></i><span><small>Gender</small><strong>{{ $patient->gender ?: 'Not provided' }}</strong></span></div>
                        <div><i class="bi bi-telephone-fill" aria-hidden="true"></i><span><small>Contact Number</small><strong>{{ $patient->contact_number ?: 'Not provided' }}</strong></span></div>
                        <div><i class="bi bi-envelope-fill" aria-hidden="true"></i><span><small>Email Address</small><strong>{{ $patient->email ?: 'Not provided' }}</strong></span></div>
                    </div>
                @endif
            </div>

            <footer class="dashboard-modal-footer">
                <button type="button" class="dashboard-modal-button secondary" data-bs-dismiss="modal">Close</button>
                @if ($patient !== null)
                    <button type="button" class="dashboard-modal-button primary" data-open-modal="editProfileModal">Edit profile</button>
                @endif
            </footer>
        </div>
    </div>
</div>

{{-- Prescriptions Modal (legacy placeholder; the header/chooser now use #patientPageModal) --}}
<div class="modal fade dashboard-modal" id="prescriptionsModal" tabindex="-1" aria-labelledby="prescriptionsModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content dashboard-modal-content">
            <header class="dashboard-modal-header">
                <div class="dashboard-modal-title-group">
                    <span class="dashboard-modal-title-icon purple" aria-hidden="true"><i class="bi bi-capsule-pill"></i></span>
                    <span>
                        <h2 id="prescriptionsModalTitle">Prescriptions</h2>
                        <small>Your prescribed medicines</small>
                    </span>
                </div>
                <button type="button" class="dashboard-modal-close" data-bs-dismiss="modal" aria-label="Close prescriptions modal">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </header>
            <div class="modal-body dashboard-modal-body">
                <div class="modal-data-loading" data-prescriptions-loading>
                    <span class="spinner-border spinner-border-sm"></span> Loading prescriptions...
                </div>
                <div class="modal-data-list" data-prescriptions-list hidden></div>
                <div class="modal-empty-state" data-prescriptions-empty hidden>
                    <i class="bi bi-capsule-pill" aria-hidden="true"></i>
                    <strong>No prescriptions found</strong>
                    <span>Your prescriptions will appear here.</span>
                </div>
            </div>
            <footer class="dashboard-modal-footer">
                <button type="button" class="dashboard-modal-button secondary" data-bs-dismiss="modal">Close</button>
            </footer>
        </div>
    </div>
</div>

{{-- Procedures Modal (legacy placeholder; the header/chooser now use #patientPageModal) --}}
<div class="modal fade dashboard-modal" id="proceduresModal" tabindex="-1" aria-labelledby="proceduresModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content dashboard-modal-content">
            <header class="dashboard-modal-header">
                <div class="dashboard-modal-title-group">
                    <span class="dashboard-modal-title-icon green" aria-hidden="true"><i class="bi bi-activity"></i></span>
                    <span>
                        <h2 id="proceduresModalTitle">Procedures</h2>
                        <small>Your procedure history</small>
                    </span>
                </div>
                <button type="button" class="dashboard-modal-close" data-bs-dismiss="modal" aria-label="Close procedures modal">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </header>
            <div class="modal-body dashboard-modal-body">
                <div class="modal-data-loading" data-procedures-loading>
                    <span class="spinner-border spinner-border-sm"></span> Loading procedures...
                </div>
                <div class="modal-data-list" data-procedures-list hidden></div>
                <div class="modal-empty-state" data-procedures-empty hidden>
                    <i class="bi bi-activity" aria-hidden="true"></i>
                    <strong>No procedures found</strong>
                    <span>Your procedures will appear here.</span>
                </div>
            </div>
            <footer class="dashboard-modal-footer">
                <button type="button" class="dashboard-modal-button secondary" data-bs-dismiss="modal">Close</button>
            </footer>
        </div>
    </div>
</div>

{{-- Edit Profile Modal --}}
<div class="modal fade dashboard-modal" id="editProfileModal" tabindex="-1" aria-labelledby="editProfileModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content dashboard-modal-content">
            <form action="{{ route('patient.profile.update') }}" method="POST" enctype="multipart/form-data" data-edit-profile-form>
                @csrf
                @method('PUT')
                <header class="dashboard-modal-header">
                    <div class="dashboard-modal-title-group">
                        <span class="dashboard-modal-title-icon navy" aria-hidden="true"><i class="bi bi-pencil-square"></i></span>
                        <span>
                            <h2 id="editProfileModalTitle">Edit Profile</h2>
                            <small>Update your personal information</small>
                        </span>
                    </div>
                    <button type="button" class="dashboard-modal-close" data-bs-dismiss="modal" aria-label="Close edit profile modal">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </button>
                </header>
                <div class="modal-body dashboard-modal-body">
                    @if ($patient !== null)
                        <div class="modal-form-grid">
                            <div class="modal-form-group">
                                <label for="editFirstName">First Name</label>
                                <input type="text" id="editFirstName" name="first_name" value="{{ $patient->first_name }}" required maxlength="100">
                            </div>
                            <div class="modal-form-group">
                                <label for="editMiddleName">Middle Name</label>
                                <input type="text" id="editMiddleName" name="middlename" value="{{ $patient->middlename }}" maxlength="100">
                            </div>
                            <div class="modal-form-group">
                                <label for="editLastName">Last Name</label>
                                <input type="text" id="editLastName" name="last_name" value="{{ $patient->last_name }}" required maxlength="100">
                            </div>
                            <div class="modal-form-group">
                                <label for="editGender">Gender</label>
                                <select id="editGender" name="gender" required>
                                    <option value="Male" @selected($patient->gender === 'Male')>Male</option>
                                    <option value="Female" @selected($patient->gender === 'Female')>Female</option>
                                </select>
                            </div>
                            <div class="modal-form-group">
                                <label for="editDob">Date of Birth</label>
                                <input type="date" id="editDob" name="dob" value="{{ $patient->dob?->format('Y-m-d') }}">
                            </div>
                            <div class="modal-form-group">
                                <label for="editContact">Contact Number</label>
                                <input type="text" id="editContact" name="contact_number" value="{{ $patient->contact_number }}" required maxlength="11">
                            </div>
                            <div class="modal-form-group">
                                <label for="editEmail">Email Address</label>
                                <input type="email" id="editEmail" name="email" value="{{ $patient->email }}" maxlength="100">
                            </div>
                            <div class="modal-form-group">
                                <label for="editAddress">Address</label>
                                <input type="text" id="editAddress" name="address" value="{{ $patient->address }}" maxlength="1000">
                            </div>
                            <div class="modal-form-group">
                                <label for="editHospitalNumber">Hospital Number</label>
                                <input type="text" id="editHospitalNumber" name="hospital_number" value="{{ $patient->hospital_number }}" maxlength="20">
                            </div>
                            <div class="modal-form-group modal-form-group-full">
                                <label for="editProfilePic">Profile Picture</label>
                                <input type="file" id="editProfilePic" name="profile_pic" accept="image/*">
                            </div>
                        </div>
                        <div class="modal-form-error" data-edit-profile-error hidden role="alert"></div>
                    @else
                        <div class="modal-empty-state">
                            <i class="bi bi-person-lock" aria-hidden="true"></i>
                            <strong>Sign in to edit your profile</strong>
                        </div>
                    @endif
                </div>
                <footer class="dashboard-modal-footer">
                    <button type="button" class="dashboard-modal-button secondary" data-bs-dismiss="modal">Cancel</button>
                    @if ($patient !== null)
                        <button type="submit" class="dashboard-modal-button primary" data-edit-profile-submit>
                            <i class="bi bi-check2-circle" aria-hidden="true"></i> Save changes
                        </button>
                    @endif
                </footer>
            </form>
        </div>
    </div>
</div>

@php
    $bookingLimits = $bookingLimits ?? ['face' => false, 'tele' => false];
    $bookingLimitFace = (bool) ($bookingLimits['face'] ?? false);
    $bookingLimitTele = (bool) ($bookingLimits['tele'] ?? false);

    // Presentation icons per consultation reason (UI only; labels/data stay the same).
    $bookingReasonIcons = [
        'dental_check_up' => 'bi-bandaid-fill',
        'prescription_refill' => 'bi-prescription',
        'laboratory_diagnostic_requests' => 'bi-droplet-fill',
        'general_check_up' => 'bi-clipboard2-pulse-fill',
        'medical_certificate_issuance' => 'bi-file-medical-fill',
        'stable_chronic_disease_follow_up' => 'bi-heart-pulse-fill',
        'results_interpretation' => 'bi-chat-dots-fill',
        'none_of_the_above' => 'bi-slash-circle-fill',
    ];
@endphp

@if (! ($bookingLimitFace && $bookingLimitTele))
    <div class="modal fade dashboard-modal dashboard-booking-modal" id="bookingModal" tabindex="-1" aria-labelledby="bookingModalTitle" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content dashboard-modal-content">
                <form action="{{ route('telemed.book.store') }}" method="POST" data-booking-form
                    data-limit-face="{{ $bookingLimitFace ? 'true' : 'false' }}"
                    data-limit-tele="{{ $bookingLimitTele ? 'true' : 'false' }}">
                    @csrf
                    <header class="dashboard-modal-header">
                        <div class="dashboard-modal-title-group">
                            <span class="dashboard-modal-title-icon" aria-hidden="true"><i class="bi bi-calendar2-plus-fill"></i></span>
                            <span>
                                <h2 id="bookingModalTitle">Request an Appointment</h2>
                                <small>Tell us what you need — our triage team will schedule your visit</small>
                            </span>
                        </div>
                        <button type="button" class="dashboard-modal-close" data-bs-dismiss="modal" aria-label="Close booking modal">
                            <i class="bi bi-x-lg" aria-hidden="true"></i>
                        </button>
                    </header>

                    <div class="modal-body dashboard-modal-body booking-modal-body">
                        <fieldset class="booking-intake-section">
                            <legend class="booking-step-label">
                                <span>01</span>
                                <span>
                                    Appointment Type
                                    <small>What would you like to consult about? (Choose one)</small>
                                    <small lang="fil">Ano ang ipapakonsulta? (Pumili ng Isa)</small>
                                </span>
                            </legend>

                            <div class="booking-reason-grid">
                                @foreach (($consultationReasons ?? []) as $reasonValue => $reasonLabel)
                                    @php
                                        $reasonSplitAt = strrpos($reasonLabel, ' / ');
                                        $reasonTitle = $reasonSplitAt === false ? $reasonLabel : substr($reasonLabel, 0, $reasonSplitAt);
                                        $reasonDescription = $reasonSplitAt === false ? 'General dental examination and cleaning.' : substr($reasonLabel, $reasonSplitAt + 3);
                                        $reasonIcon = $bookingReasonIcons[$reasonValue] ?? 'bi-calendar2-plus-fill';
                                    @endphp
                                    <label class="booking-choice-card booking-reason-card">
                                        <input
                                            type="radio"
                                            name="consultation_reason"
                                            value="{{ $reasonValue }}"
                                            @checked(old('consultation_reason') === $reasonValue)
                                            data-booking-reason
                                            required
                                        >
                                        <span class="booking-choice-icon" aria-hidden="true"><i class="bi {{ $reasonIcon }}"></i></span>
                                        <span class="booking-choice-copy">
                                            <strong>{{ $reasonTitle }}</strong>
                                            <small>{{ $reasonDescription }}</small>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>

                        <div data-symptom-section hidden>
                            <fieldset class="booking-intake-section">
                                <legend class="booking-step-label booking-symptom-heading">
                                    <span>02</span>
                                    <span>
                                        Symptoms
                                        <small>Please select at least 1 and maximum of 3 symptoms.</small>
                                        <small lang="fil">Pumili ng Isa o hanggang sa Tatlong Sintomas</small>
                                    </span>
                                    <strong data-booking-symptom-count aria-live="polite">0 / 3 selected</strong>
                                </legend>

                                <div class="booking-symptom-grid" data-booking-symptoms>
                                    @foreach (($symptoms ?? []) as $symptomValue => $symptomLabel)
                                        <label class="booking-choice-card booking-symptom-card">
                                            <input
                                                type="checkbox"
                                                name="symptoms[]"
                                                value="{{ $symptomValue }}"
                                                @checked(in_array($symptomValue, old('symptoms', []), true))
                                                data-booking-symptom
                                            >
                                            <span>{{ $symptomLabel }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>

                            <fieldset class="booking-intake-section">
                                <legend class="booking-step-label">
                                    <span>03</span>
                                    <span>
                                        Tell Us About Your Concern
                                        <small>Details about your Complaint</small>
                                        <small lang="fil">Magbigay ng konting detalye ukol sa inyong karamdaman</small>
                                    </span>
                                </legend>

                                <label class="visually-hidden" for="bookingComplaintDetails">Details about your complaint</label>
                                <p class="booking-complaint-hint">Keep the details clear and medically relevant.</p>
                                <textarea
                                    class="booking-complaint-input"
                                    id="bookingComplaintDetails"
                                    name="complaint_details"
                                    rows="5"
                                    maxlength="2000"
                                    placeholder="e.g. Duration of symptoms, previous medical history, current medications, etc."
                                    data-booking-complaint-details
                                >{{ old('complaint_details') }}</textarea>
                                <div class="booking-character-count">
                                    <span data-booking-detail-count>0 / 2000</span>
                                </div>
                            </fieldset>
                        </div>

                        <div class="booking-intake-error" data-booking-intake-error hidden role="alert"></div>
                    </div>

                    <footer class="dashboard-modal-footer booking-modal-footer" data-request-actions hidden>
                        <span class="dashboard-modal-footer-note"><i class="bi bi-shield-check" aria-hidden="true"></i> Your consent has been recorded for this request.</span>
                        <button type="button" class="dashboard-modal-button secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="dashboard-modal-button primary" data-booking-submit disabled>
                            <i class="bi bi-send-fill" aria-hidden="true"></i> Submit Request
                        </button>
                    </footer>
                </form>
            </div>
        </div>
    </div>
@endif

<div class="modal fade dashboard-modal booking-qr-modal" id="bookingQrModal" tabindex="-1" aria-labelledby="bookingQrModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content dashboard-modal-content">
            <header class="dashboard-modal-header">
                <div class="dashboard-modal-title-group">
                    <span class="dashboard-modal-title-icon green" aria-hidden="true"><i class="bi bi-check2-circle"></i></span>
                    <span>
                        <h2 id="bookingQrModalTitle">Appointment confirmed</h2>
                        <small>Show this QR code at the kiosk</small>
                    </span>
                </div>
                <button type="button" class="dashboard-modal-close" data-bs-dismiss="modal" aria-label="Close appointment confirmation">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </header>
            <div class="modal-body booking-qr-body">
                <div class="booking-qr-success-icon" aria-hidden="true"><i class="bi bi-calendar2-check-fill"></i></div>
                <h3>Your telemedicine visit is booked</h3>
                <p>Click the QR code below to make it larger for easy scanning.</p>

                <button type="button" class="booking-qr-image-button" data-booking-qr-expand aria-label="Enlarge appointment QR code">
                    <img data-booking-qr-image alt="Appointment verification QR code" src="">
                </button>

                <div class="booking-qr-summary">
                    <span data-booking-qr-service>Telemedicine consultation</span>
                    <strong data-booking-qr-datetime>Schedule confirmed</strong>
                </div>
                <p class="booking-qr-security-note">
                    <i class="bi bi-shield-check" aria-hidden="true"></i>
                    The kiosk will verify this code against your active appointment.
                </p>
            </div>
            <footer class="dashboard-modal-footer">
                <button type="button" class="dashboard-modal-button secondary" data-bs-dismiss="modal">Close</button>
                <a class="dashboard-modal-button primary" href="{{ route('telemed.mine') }}">
                    View my appointments <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </a>
            </footer>
        </div>
    </div>
</div>

<div class="modal fade qr-enlarge-modal" id="bookingQrEnlargeModal" tabindex="-1" aria-labelledby="bookingQrEnlargeTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <header class="qr-enlarge-header">
                <div>
                    <h2 id="bookingQrEnlargeTitle">Appointment QR code</h2>
                    <span>Hold your phone steady and scan this code at the kiosk.</span>
                </div>
                <button type="button" class="qr-enlarge-close" data-bs-dismiss="modal" aria-label="Close enlarged QR code">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </header>
            <div class="modal-body qr-enlarge-body">
                <img data-booking-qr-enlarged-image alt="Enlarged appointment verification QR code" src="">
            </div>
        </div>
    </div>
</div>


<div class="modal fade dashboard-modal dashboard-alert-modal" id="cancelledAppointmentModal" tabindex="-1" aria-labelledby="cancelledAppointmentModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content dashboard-modal-content">
            <header class="dashboard-alert-header">
                <span aria-hidden="true"><i class="bi bi-calendar-x-fill"></i></span>
                <button type="button" class="dashboard-modal-close light" data-bs-dismiss="modal" aria-label="Close cancelled appointment notice">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </header>
            <div class="modal-body dashboard-alert-body">
                <span class="dashboard-alert-badge">Appointment cancelled</span>
                <h2 id="cancelledAppointmentModalTitle">This appointment is already cancelled</h2>
                <p>You cannot join a cancelled telemedicine visit. Please choose a new schedule if you still need medical care.</p>
                <div class="cancelled-appointment-summary">
                    <span><i class="bi bi-heart-pulse" aria-hidden="true"></i> <strong data-cancelled-service>Consultation</strong></span>
                    <span><i class="bi bi-calendar3" aria-hidden="true"></i> <strong data-cancelled-date>—</strong></span>
                    <span><i class="bi bi-clock" aria-hidden="true"></i> <strong data-cancelled-time>—</strong></span>
                </div>
            </div>
            <footer class="dashboard-alert-footer">
                <button type="button" class="dashboard-modal-button secondary" data-bs-dismiss="modal">Close</button>
                @if ($patient !== null)
                    <button type="button" class="dashboard-modal-button danger" data-open-consent>
                        <i class="bi bi-calendar2-plus" aria-hidden="true"></i> Book a new visit
                    </button>
                @endif
            </footer>
        </div>
    </div>
</div>

{{-- ===================== History Modal ===================== --}}
<div class="modal fade dashboard-modal" id="historyModal" tabindex="-1" aria-labelledby="historyModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content dashboard-modal-content">
            <header class="dashboard-modal-header">
                <div class="dashboard-modal-title-group">
                    <span class="dashboard-modal-title-icon blue" aria-hidden="true"><i class="bi bi-clock-history"></i></span>
                    <span>
                        <h2 id="historyModalTitle">Appointment History</h2>
                        <small>Your past and completed appointments</small>
                    </span>
                </div>
                <button type="button" class="dashboard-modal-close" data-bs-dismiss="modal" aria-label="Close history modal">
                    <i class="bi bi-x-lg" aria-hidden="true"></i>
                </button>
            </header>
            <div class="modal-body dashboard-modal-body">
                <div data-history-loading class="text-center py-4">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <span class="ms-2">Loading history...</span>
                </div>
                <div data-history-empty class="text-center py-4" hidden>
                    <i class="bi bi-calendar-x" aria-hidden="true" style="font-size: 2rem; color: #94a3b8;"></i>
                    <p class="mt-2 mb-0 text-muted">No past appointments found.</p>
                </div>
                <div data-history-list class="table-responsive" hidden>
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Service</th>
                                <th>Mode</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody data-history-tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ===================== Shared page modal (Medical Records / Prescriptions / Procedures / Profile) ===================== --}}
<div class="modal fade" id="patientPageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold" data-page-modal-title></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" data-page-modal-body></div>
        </div>
    </div>
</div>

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="{{ asset('js/records.js') }}"></script>
    {{-- Print/Renew on the prescriptions page when it opens inside #patientPageModal. --}}
    <script src="{{ asset('js/rx-prescriptions.js') }}" defer></script>
    <script>
        (() => {
            const modalEl = document.getElementById('patientPageModal');
            if (!modalEl) return;

            const body = modalEl.querySelector('[data-page-modal-body]');
            const title = modalEl.querySelector('[data-page-modal-title]');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            const headers = { 'X-Requested-With': 'XMLHttpRequest' };
            const spinner = '<div class="text-center py-5"><div class="spinner-border text-primary"></div></div>';
            let currentUrl = null;

            const load = async (url, flash = '') => {
                currentUrl = url;
                body.innerHTML = spinner;
                document.querySelectorAll('[data-hoisted]').forEach((el) => el.remove());

                try {
                    const res = await fetch(url, { headers: { ...headers, Accept: 'text/html' } });
                    if (!res.ok) throw new Error(res.status);
                    body.innerHTML = flash + await res.text();

                    // Nested modals (records add/edit/delete) must live on <body> to stack properly.
                    body.querySelectorAll('.modal').forEach((m) => {
                        m.dataset.hoisted = '1';
                        document.body.appendChild(m);
                    });
                } catch (e) {
                    body.innerHTML = '<div class="alert alert-danger">Could not load this section. Please try again.</div>';
                }
            };

            document.addEventListener('click', (e) => {
                const trigger = e.target.closest('[data-patient-modal]');
                if (!trigger) return;
                e.preventDefault();
                title.textContent = trigger.dataset.title || '';
                // Close any other open modal first (e.g. the "Health Records" chooser).
                document.querySelectorAll('.modal.show').forEach((m) => {
                    if (m !== modalEl) bootstrap.Modal.getInstance(m)?.hide();
                });
                modal.show();
                load(trigger.getAttribute('href') || trigger.dataset.url);
            });

            // Clean up hoisted modals when the main modal closes.
            modalEl.addEventListener('hidden.bs.modal', () => {
                document.querySelectorAll('[data-hoisted]').forEach((el) => el.remove());
                body.innerHTML = '';
            });

            // Save the profile form inside the modal.
            body.addEventListener('submit', async (e) => {
                const form = e.target.closest('form[data-modal-form]');
                if (!form) return;
                e.preventDefault();

                const btn = form.querySelector('[type=submit]');
                btn.disabled = true;

                let res;
                try {
                    res = await fetch(form.action, {
                        method: 'POST',
                        body: new FormData(form), // includes _token and _method=PUT
                        headers: { ...headers, Accept: 'application/json' },
                    });
                } catch (err) {
                    btn.disabled = false;
                    body.insertAdjacentHTML('afterbegin', '<div class="alert alert-danger">Network error. Please try again.</div>');
                    return;
                }

                if (res.status === 422) {
                    const { errors = {} } = await res.json();
                    form.querySelectorAll('.is-invalid').forEach((i) => i.classList.remove('is-invalid'));
                    form.querySelectorAll('.js-err').forEach((i) => i.remove());
                    Object.entries(errors).forEach(([field, msgs]) => {
                        const input = form.querySelector(`[name="${field}"]`);
                        if (!input) return;
                        input.classList.add('is-invalid');
                        input.insertAdjacentHTML('afterend', `<div class="invalid-feedback d-block js-err">${msgs[0]}</div>`);
                    });
                    btn.disabled = false;
                } else if (res.ok) {
                    await load(currentUrl, '<div class="alert alert-success">Profile updated.</div>');
                } else {
                    btn.disabled = false;
                    body.insertAdjacentHTML('afterbegin', '<div class="alert alert-danger">Could not save. Please try again.</div>');
                }
            });
        })();
    </script>
@endpush