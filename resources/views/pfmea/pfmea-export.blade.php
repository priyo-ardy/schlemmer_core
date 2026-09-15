<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>PFMEA Document</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 3mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 5.5pt;
            color: #000000 !important;
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: separate !important;
            border-spacing: 0 !important;
            table-layout: fixed;
            margin: 0;
            padding: 0;
            border-top: 0.8pt solid #000000 !important;
            border-left: 0.8pt solid #000000 !important;
        }

        th,
        td {
            border-top: none !important;
            border-left: none !important;
            border-right: 0.8pt solid #000000 !important;
            border-bottom: 0.8pt solid #000000 !important;
            padding: 1.5pt 2pt;
            word-wrap: break-word;
            overflow: hidden;
            vertical-align: middle;
            color: #000000 !important;
            background-color: #ffffff !important;
        }

        /* FIX GARIS ATAS HEADER HALAMAN 2 DST */
        thead th {
            border-top: 0.8pt solid #000000 !important;
        }

        table table {
            border-top: none !important;
            border-left: none !important;
        }

        /* Sub-tabel header tidak perlu border atas ekstra */
        table table th {
            border-top: none !important;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-left {
            text-align: left;
        }

        .text-bold {
            font-weight: bold;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid !important;
        }
    </style>
</head>

<body>

    <!-- 1. HEADER SECTION -->
    <table>
        <tr>
            <td style="width: 12%;" class="text-center" rowspan="2">
                <img src="{{ public_path('storage/logo.webp') }}" style="max-height: 35px; max-width: 100%;">
            </td>
            <td style="width: 53%;" class="text-center" rowspan="2">
                <div style="font-size: 11pt;" class="text-bold">Schlemmer Automotive Indonesia</div>
                <div style="font-size: 10pt; margin-top: 2px;" class="text-bold">PFMEA</div>
            </td>
            <td style="width: 35%; padding: 0;" rowspan="2">
                <table style="border: none;">
                    <tr>
                        <td style="width: 30%; border-top: none; border-left: none;" class="text-bold">Issue Date :</td>
                        <td style="width: 40%; border-top: none;" class="text-bold">{{ $pfmea['issue_date'] ?? '-' }}</td>
                        <td style="width: 30%; border-top: none; border-right: none;" class="text-bold">Page: {{ $pfmea['page'] ?? '1 of 1' }}</td>
                    </tr>
                    <tr>
                        <td style="border-left: none;" class="text-bold">Issuing Dept.:</td>
                        <td class="text-bold">{{ $pfmea['issuing_dept'] ?? 'R&D Dept.' }}</td>
                        <td style="border-right: none;" class="text-bold">Version: {{ $pfmea['version'] ?? '1.0' }}</td>
                    </tr>
                    <tr class="text-center text-bold">
                        <td style="border-left: none;">Approved</td>
                        <td>Reviewed</td>
                        <td style="border-right: none;">Prepared</td>
                    </tr>
                    <tr class="text-center">
                        <td style="border-left: none;">{{ $pfmea['approved_by'] ?? '-' }}</td>
                        <td>{{ $pfmea['reviewed_by'] ?? '-' }}</td>
                        <td style="border-right: none;">{{ $pfmea['prepared_by'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td colspan="3" class="text-bold text-right" style="border-bottom: none; border-left: none; border-right: none;">
                            No.: {{ $pfmea['doc_no'] ?? '-' }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- 2. SCOPE, PROJECT INFO, REVISION HISTORY -->
    <table style="border-top: none;">
        <tr>
            <td style="width: 15%; padding: 0; vertical-align: top;">
                <table style="border: none;">
                    <tr class="text-bold">
                        <td style="border-top: none; border-left: none; width: 40%;" rowspan="4" class="text-center">Scope</td>
                        <td style="border-top: none; width: 50%;">Prototype</td>
                        <td style="border-top: none; border-right: none; width: 10%;" class="text-center">{{ ($pfmea['scope'] ?? '') === 'prototype' ? 'v' : '' }}</td>
                    </tr>
                    <tr>
                        <td>Pre-Launch</td>
                        <td style="border-right: none;" class="text-center">{{ ($pfmea['scope'] ?? '') === 'pre_launch' ? 'v' : '' }}</td>
                    </tr>
                    <tr>
                        <td>Containment EPC</td>
                        <td style="border-right: none;" class="text-center">{{ ($pfmea['scope'] ?? '') === 'Containment EPC' ? 'v' : '' }}</td>
                    </tr>
                    <tr>
                        <td style="border-bottom: none;">Mass Production</td>
                        <td style="border-right: none; border-bottom: none;" class="text-center">{{ ($pfmea['scope'] ?? '') === 'mass_production' ? 'v' : '' }}</td>
                    </tr>
                </table>
            </td>

            <td style="width: 48%; padding: 0; vertical-align: top;">
                <table style="border: none;">
                    <tr>
                        <td style="width: 30%; border-top: none; border-left: none;" class="text-bold">Project Name:</td>
                        <td style="border-top: none; border-right: none;" class="text-bold" colspan="3">{{ $pfmea['part_name'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-bold" style="border-left: none;">Key Date:</td>
                        <td class="text-bold" style="border-right: none;" colspan="3">{{ $pfmea['key_date'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-bold" style="border-left: none;">Part Name:</td>
                        <td class="text-bold" style="border-right: none;" colspan="3">{{ $pfmea['part_name'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-bold" style="border-left: none;">Part No./Level:</td>
                        <td class="text-bold" style="border-right: none;" colspan="3">{{ $pfmea['part_no'] ?? '-' }} / {{ $pfmea['dwg_no'] ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td colspan="4" style="border-left: none; border-right: none; border-bottom: none;">
                            <b>Process Responsibility:</b> {{ $pfmea['process_responsibility'] ?? 'Development, Manufacturing, Quality, Business, Logistics' }}
                        </td>
                    </tr>
                </table>
            </td>

            <td style="width: 37%; padding: 0; vertical-align: top;">
                <div class="text-center text-bold" style="border-bottom: 0.8pt solid #000; padding: 1.5pt;">Revision History</div>
                <table style="border: none;">
                    <tr class="text-bold text-center">
                        <td style="width: 10%; border-left: none;">Ver.</td>
                        <td style="width: 22%;">Date</td>
                        <td>Content</td>
                        <td style="width: 15%;">Approved</td>
                        <td style="width: 15%;">Reviewed</td>
                        <td style="width: 15%; border-right: none;">Prepared</td>
                    </tr>
                    @forelse($pfmea['revision_history'] ?? [] as $rev)
                    <tr class="text-center">
                        <td class="text-bold" style="border-left: none; border-bottom: none;">{{ $rev['ver'] ?? '-' }}</td>
                        <td style="border-bottom: none;">{{ isset($rev['date']) ? date('d/m/Y', strtotime($rev['date'])) : '-' }}</td>
                        <td style="text-align: left; border-bottom: none;">{{ $rev['content'] ?? '-' }}</td>
                        <td style="border-bottom: none;">{{ $pfmea['approved_by'] ?? '-' }}</td>
                        <td style="border-bottom: none;">{{ $pfmea['reviewed_by'] ?? '-' }}</td>
                        <td style="border-right: none; border-bottom: none;">{{ $pfmea['prepared_by'] ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr class="text-center">
                        <td colspan="6" style="border-left: none; border-right: none; border-bottom: none;">No revision records</td>
                    </tr>
                    @endforelse
                </table>
            </td>
        </tr>
    </table>

    <!-- 3. CORE TEAM SECTION -->
    <table style="border-top: none;">
        <tr>
            <td class="text-bold" style="padding: 2.5pt;">
                Core Team: <span style="font-weight: normal; margin-left: 5px;">{{ $pfmea['core_team'] ?? '-' }}</span>
            </td>
        </tr>
    </table>

    <!-- 4. READ-ONLY WORKSHEET TABLE (FLAT TABLE DENGAN 21 KOLOM SYMMETRIC) -->
    <table style="border-top: none;">
        <thead>
            <tr class="text-center text-bold">
                <th rowspan="2" style="width: 6.5%;">Process Step / Function</th>
                <th rowspan="2" style="width: 4%;">Kakotora YC</th>
                <th rowspan="2" style="width: 7%;">Requirements</th>
                <th rowspan="2" style="width: 7.5%;">Potential Failure Mode</th>
                <th rowspan="2" style="width: 8%;">Potential Effect(s) of Failure</th>
                <th rowspan="2" style="width: 2.5%;">Severity</th>
                <th rowspan="2" style="width: 2.5%;">Classification</th>
                <th rowspan="2" style="width: 8%;">Potential Cause(s) of Failure</th>
                <th rowspan="2" style="width: 2.5%;">Occurrence</th>
                <th rowspan="2" style="width: 7%;">Controls Prevention</th>
                <th rowspan="2" style="width: 7%;">Control Detection</th>
                <th rowspan="2" style="width: 2.5%;">Detection</th>
                <th rowspan="2" style="width: 3%;">RPN</th>
                <th rowspan="2" style="width: 7%;">Recommended Action(s)</th>
                <th rowspan="2" style="width: 5.5%;">Responsibility</th>
                <th rowspan="2" style="width: 4.5%;">Target Completion Date</th>
                <th colspan="5" style="width: 15.5%;">Result</th>
            </tr>
            <tr class="text-center text-bold">
                <th style="width: 4.5%;">Action Taken Completion Date</th>
                <th style="width: 2.5%;">Severity</th>
                <th style="width: 2.5%;">Occurrence</th>
                <th style="width: 2.5%;">Detection</th>
                <th style="width: 3.5%;">RPN</th>
            </tr>
        </thead>
        <tbody>
            @php
            $prevStep = null;
            $prevControl = null;
            @endphp

            @forelse($items as $item)
            @php
            $currentStep = $item['process_step'] ?? '-';
            $currentControl = $item['control_detection'] ?? '-';

            // Pengecekan duplikasi step & control dengan baris sebelumnya
            $isSameStep = ($currentStep === $prevStep);
            $isSameControl = ($isSameStep && $currentControl === $prevControl);

            $prevStep = $currentStep;
            $prevControl = $currentControl;
            @endphp
            <tr>
                <!-- Kolom 1: Process Step (Selalu dirender <td> agar posisi 21 kolom simetris) -->
                <td class="text-bold text-left">
                    {{ !$isSameStep ? $currentStep : '' }}
                </td>

                <td class="text-center">{{ $item['kakotora_yc'] ?? '-' }}</td>
                <td>{{ $item['requirements'] ?? '-' }}</td>
                <td>{{ $item['potential_failure_mode'] ?? '-' }}</td>
                <td>{{ $item['potential_effects'] ?? '-' }}</td>
                <td class="text-center text-bold">{{ $item['severity'] ?? '-' }}</td>
                <td class="text-center">{{ $item['classification'] ?? '-' }}</td>
                <td>{{ $item['potential_causes'] ?? '-' }}</td>
                <td class="text-center text-bold">{{ $item['occurrence'] ?? '-' }}</td>
                <td>{{ $item['controls_prevention'] ?? '-' }}</td>

                <!-- Kolom 11: Control Detection -->
                <td>
                    {{ !$isSameControl ? $currentControl : '' }}
                </td>

                <td class="text-center text-bold">{{ $item['detection'] ?? '-' }}</td>
                <td class="text-center text-bold">{{ $item['rpn'] ?? '-' }}</td>
                <td>{{ $item['recommended_actions'] ?? '-' }}</td>
                <td>{{ $item['responsibility'] ?? '-' }}</td>
                <td class="text-center">{{ $item['target_completion_date'] ?? '-' }}</td>
                <td class="text-center">{{ $item['action_taken_date'] ?? '-' }}</td>
                <td class="text-center">{{ $item['result_severity'] ?? '-' }}</td>
                <td class="text-center">{{ $item['result_occurrence'] ?? '-' }}</td>
                <td class="text-center">{{ $item['result_detection'] ?? '-' }}</td>
                <td class="text-center text-bold">{{ $item['result_rpn'] ?? '-' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="21" class="text-center" style="padding: 10px;">
                    No PFMEA items available.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

</body>

</html>