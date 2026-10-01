@extends('layouts.admin')

@section('title', 'Patient medical records')

@section('sidebar')
    @include('partials.admin-sidebar')
@end

@section('header')
    @include('partials.admin-header')
@end

@section('content')
    @php
        $patientName = trim(implode(' ', array_filter([
            $patient->first_name,
            $patient->middlename,
            $patient->last_name,
        ])));
    @endphp

    <div class="admin-dashboard-content admin-doctor-content">
        <nav class="admin-breadcrumb-nav" aria-label="Patient records navigation">
            <a class="admin-breadcrumb-link" href="{{ route('admin.patients', ['view' => $patient->id]) }}">
                <i class="bi bi-arrow-left" aria-hidden="true"></i>
                Back to patient profile
            </a>
        </nav>

        <section class="admin-panel" aria-labelledby="patientRecordsTitle">
            <header class="admin-panel-header">
                <div class="admin-panel-title">
                    <i class="bi bi-file-earmark-medical" aria-hidden="true"></i>
                    <h1 id="patientRecordsTitle" class="admin-panel-page-title">Medical records</h1>
                </div>
                <span class="admin-muted-text">{{ $medicalRecords->total() }} record{{ $medicalRecords->total() === 1 ? '' : 's' }}</span>
            </header>

            <div class="admin-doctor-table-wrap">
                <table class="admin-doctor-table">
                    <thead>
                        <tr>
                            <th scope="col">Type</th>
                            <th scope="col">Description</th>
                            <th scope="col">File</th>
                            <th scope="col">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($medicalRecords as $record)
                            <tr>
                                <td>{{ $record->record_type ?: '—' }}</td>
                                <td>{{ $record->description ?: '—' }}</td>
                                <td>
                                    @if ($record->file_path)
                                        <i class="bi bi-paperclip" aria-hidden="true"></i> Attachment
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $record->created_at?->format('M j, Y') ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="admin-doctor-empty">
                                        <i class="bi bi-journal-medical" aria-hidden="true"></i>
                                        <strong>No medical records on file</strong>
                                        <span>Medical records uploaded for this patient will appear here.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($medicalRecords->hasPages())
                <div class="admin-doctor-pagination">{{ $medicalRecords->links('pagination::bootstrap-5') }}</div>
            @endif
        </section>

        <section class="admin-panel" aria-labelledby="patientPrescriptionsTitle">
            <header class="admin-panel-header">
                <div class="admin-panel-title">
                    <i class="bi bi-capsule" aria-hidden="true"></i>
                    <h1 id="patientPrescriptionsTitle" class="admin-panel-page-title">Prescriptions</h1>
                </div>
                <span class="admin-muted-text">{{ $prescriptions->total() + count($homisPrescriptions) }} prescription{{ ($prescriptions->total() + count($homisPrescriptions)) === 1 ? '' : 's' }}</span>
            </header>

            <div class="admin-doctor-table-wrap">
                <table class="admin-doctor-table">
                    <thead>
                        <tr>
                            <th scope="col">Prescribed by</th>
                            <th scope="col">Medicine</th>
                            <th scope="col">Instructions</th>
                            <th scope="col">Date</th>
                            <th scope="col">Source</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($prescriptions as $prescription)
                            <tr>
                                <td>{{ $prescription->doctor_name ?: '—' }}</td>
                                <td>{{ $prescription->medicine ?: '—' }}</td>
                                <td>{{ $prescription->instructions ?: '—' }}</td>
                                <td>{{ $prescription->created_at?->format('M j, Y') ?: '—' }}</td>
                                <td>Local</td>
                            </tr>
                        @empty
                            @if (count($homisPrescriptions) === 0)
                                <tr>
                                    <td colspan="5">
                                        <div class="admin-doctor-empty">
                                            <i class="bi bi-capsule" aria-hidden="true"></i>
                                            <strong>No prescriptions on file</strong>
                                            <span>Prescriptions issued to this patient will appear here.</span>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforelse
                        @foreach ($homisPrescriptions as $homisPrescription)
                            <tr>
                                <td>{{ $homisPrescription['doctor_name'] ?: '—' }}</td>
                                <td>{{ $homisPrescription['medicine'] ?: '—' }}</td>
                                <td>{{ $homisPrescription['instructions'] ?: '—' }}</td>
                                <td>{{ $homisPrescription['date'] ? \Carbon\Carbon::parse($homisPrescription['date'])->format('M j, Y') : '—' }}</td>
                                <td>HOMIS</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($prescriptions->hasPages())
                <div class="admin-doctor-pagination">{{ $prescriptions->links('pagination::bootstrap-5') }}</div>
            @endif
        </section>

        <section class="admin-panel" aria-labelledby="patientProceduresTitle">
            <header class="admin-panel-header">
                <div class="admin-panel-title">
                    <i class="bi bi-clipboard2-pulse" aria-hidden="true"></i>
                    <h1 id="patientProceduresTitle" class="admin-panel-page-title">Procedures</h1>
                </div>
                <span class="admin-muted-text">{{ count($homisProcedures) }} procedure{{ count($homisProcedures) === 1 ? '' : 's' }}</span>
            </header>

            <div class="admin-doctor-table-wrap">
                <table class="admin-doctor-table">
                    <thead>
                        <tr>
                            <th scope="col">Procedure</th>
                            <th scope="col">Cost center</th>
                            <th scope="col">Result</th>
                            <th scope="col">Date</th>
                            <th scope="col">Source</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($homisProcedures as $homisProcedure)
                            <tr>
                                <td>{{ $homisProcedure['procedure'] ?: '—' }}</td>
                                <td>{{ $homisProcedure['cost_center'] ?: '—' }}</td>
                                <td>
                                    @if ($homisProcedure['result_available'])
                                        <span class="admin-status-pill completed">Available</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $homisProcedure['date'] ? \Carbon\Carbon::parse($homisProcedure['date'])->format('M j, Y') : '—' }}</td>
                                <td>HOMIS</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <div class="admin-doctor-empty">
                                        <i class="bi bi-clipboard2-pulse" aria-hidden="true"></i>
                                        <strong>No procedures on file</strong>
                                        <span>Procedures ordered for this patient will appear here.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="admin-panel" aria-labelledby="patientVisitsTitle">
            <header class="admin-panel-header">
                <div class="admin-panel-title">
                    <i class="bi bi-clock-history" aria-hidden="true"></i>
                    <h1 id="patientVisitsTitle" class="admin-panel-page-title">Visit history</h1>
                </div>
                <span class="admin-muted-text">{{ count($homisVisits) }} visit{{ count($homisVisits) === 1 ? '' : 's' }}</span>
            </header>

            <div class="admin-doctor-table-wrap">
                <table class="admin-doctor-table">
                    <thead>
                        <tr>
                            <th scope="col">Visit type</th>
                            <th scope="col">Encounter code</th>
                            <th scope="col">Date</th>
                            <th scope="col">Source</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($homisVisits as $homisVisit)
                            <tr>
                                <td>{{ $homisVisit['visit_type'] ?: '—' }}</td>
                                <td>{{ $homisVisit['encounter_code'] ?: '—' }}</td>
                                <td>{{ $homisVisit['date'] ? \Carbon\Carbon::parse($homisVisit['date'])->format('M j, Y') : '—' }}</td>
                                <td>HOMIS</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="admin-doctor-empty">
                                        <i class="bi bi-clock-history" aria-hidden="true"></i>
                                        <strong>No visits on file</strong>
                                        <span>Hospital visit history for this patient will appear here.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection