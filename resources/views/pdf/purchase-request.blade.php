<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page { margin: 15mm; }
    body {
        font-family: sans-serif;
        font-size: 10px;
        color: #000;
    }
    table {
        border-collapse: collapse;
    }
    .gov-header {
        width: 100%;
        margin-bottom: 8px;
    }
    .logo-box {
        border: 1px solid #999;
        width: 58px;
        height: 58px;
        text-align: center;
        font-size: 8px;
        padding: 4px;
        line-height: 1.4;
    }
    .agency-name {
        text-align: center;
        font-size: 11px;
        line-height: 1.6;
        vertical-align: middle;
    }
    .doc-title {
        text-align: center;
        font-size: 13px;
        font-weight: bold;
        text-decoration: underline;
        letter-spacing: 2px;
        margin: 10px 0 8px 0;
    }
    .info-table {
        width: 100%;
        margin-bottom: 10px;
    }
    .info-table td {
        padding: 3px 4px;
    }
    .items-table {
        width: 100%;
        margin-bottom: 12px;
    }
    .items-table th,
    .items-table td {
        border: 1px solid #000;
        padding: 4px 5px;
    }
    .items-table th {
        background-color: #d8d8d8;
        text-align: center;
        font-size: 9px;
    }
    .items-table td {
        vertical-align: top;
    }
    .cert-box {
        width: 100%;
        border: 1px solid #000;
        padding: 8px;
        margin-bottom: 10px;
    }
    .sig-line {
        text-align: center;
        border-top: 1px solid #000;
        margin-top: 30px;
        padding-top: 4px;
    }
    .footer {
        text-align: center;
        font-size: 8px;
        margin-top: 18px;
        border-top: 1px solid #000;
        padding-top: 5px;
    }
</style>
</head>
<body>

    {{-- Government Header --}}
    <table class="gov-header">
        <tr>
            <td style="width: 68px; text-align: center; vertical-align: middle;">
                <div class="logo-box">PH<br>SEAL</div>
            </td>
            <td style="width: 68px; text-align: center; vertical-align: middle;">
                <div class="logo-box">NIA<br>LOGO</div>
            </td>
            <td class="agency-name">
                Republic of the Philippines<br>
                OFFICE OF THE PRESIDENT<br>
                <strong>NATIONAL IRRIGATION ADMINISTRATION</strong><br>
                CARAGA REGION
            </td>
            <td style="width: 68px; text-align: center; vertical-align: middle;">
                <div class="logo-box">BAGONG<br>PILIPINAS</div>
            </td>
        </tr>
    </table>

    <div class="doc-title">PURCHASE REQUEST</div>

    {{-- PR Info --}}
    <table class="info-table">
        <tr>
            <td style="width: 55%;"><strong>Section:</strong> {{ $pr->requestingOffice?->name }}</td>
            <td style="width: 45%;"><strong>PR No.:</strong> {{ $pr->pr_number ?? 'TBA' }}</td>
        </tr>
        <tr>
            <td><strong>Unit:</strong> {{ $pr->requestingOffice?->name }}</td>
            <td><strong>Date:</strong> {{ $pr->submitted_at?->format('F d, Y') }}</td>
        </tr>
    </table>

    {{-- Items Table --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 7%;">Item<br>No.</th>
                <th style="width: 9%;">Unit</th>
                <th style="width: 35%;">Item Description</th>
                <th style="width: 11%;">Quantity</th>
                <th style="width: 19%;">Estimated<br>Unit Cost</th>
                <th style="width: 19%;">Estimated<br>Total Cost</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($pr->items as $index => $item)
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td style="text-align: center;">{{ $item->unit_of_measure }}</td>
                <td>
                    {{ $item->item_description }}
                    @if ($item->specifications)
                    <br><span style="font-size: 9px; font-style: italic;">{{ $item->specifications }}</span>
                    @endif
                </td>
                <td style="text-align: center;">{{ $item->quantity }}</td>
                <td style="text-align: right;">{{ number_format((float) $item->unit_cost, 2) }}</td>
                <td style="text-align: right;">{{ number_format((float) $item->total_cost, 2) }}</td>
            </tr>
            @endforeach
            {{-- Empty rows to pad the table for handwriting --}}
            @for ($i = 0; $i < max(0, 5 - $pr->items->count()); $i++)
            <tr>
                <td style="height: 18px;">&nbsp;</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            @endfor
        </tbody>
    </table>

    <p><strong>APPROVED BUDGET:</strong> P {{ number_format((float) $pr->total_amount, 2) }}</p>

    {{-- Certification Block --}}
    <table style="width: 100%; margin-bottom: 10px;">
        <tr>
            <td style="border: 1px solid #000; padding: 8px;">
                <strong>CERTIFICATION</strong><br>
                This is to certify that the (commodities, works or services) being requested herein is/are within the
                approved Program of Work and is/are included in the approved APP of the Agency for {{ now()->year }}.
                <div class="sig-line" style="margin-top: 24px;">
                    {{ $pr->requester->full_name }}<br>
                    <span style="font-size: 9px;">{{ $pr->requester->role?->name ?? '' }}</span>
                </div>
            </td>
        </tr>
    </table>

    <p><strong>PURPOSE:</strong> {{ $pr->purpose }}</p>

    {{-- Signature Section --}}
    <table style="width: 100%; margin-top: 16px;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 10px;">
                Signature ___________________<br>
                Printed Name: {{ $pr->requester->full_name }}<br>
                Designation: {{ $pr->requester->role?->name ?? '' }}
            </td>
            <td style="width: 50%; text-align: center; vertical-align: top;">
                <strong>Approved by:</strong>
                <div class="sig-line" style="margin-top: 24px;">
                    Regional Manager A
                </div>
            </td>
        </tr>
    </table>

    <p style="margin-top: 14px;">
        <strong>CERTIFIED AS TO AVAILABILITY OF FUNDS:</strong> _______________&nbsp;&nbsp;
        <strong>BY:</strong> _______________
    </p>

    {{-- Footer --}}
    <div class="footer">
        NIA Compound, Brgy. Bancasi, Butuan City, 8600 Philippines<br>
        Telefax Number: (085) 815 26 02 &nbsp;|&nbsp; Email: r13@nia.gov.ph &nbsp;|&nbsp; Website: caraga.nia.gov.ph &nbsp;|&nbsp; TIN: 000916415134
    </div>

</body>
</html>
