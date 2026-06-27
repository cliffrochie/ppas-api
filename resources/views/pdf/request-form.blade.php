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
    .header-table {
        width: 100%;
        margin-bottom: 8px;
    }
    .logo-box {
        border: 1px solid #999;
        width: 60px;
        height: 60px;
        text-align: center;
        font-size: 8px;
        padding: 4px;
        line-height: 1.4;
    }
    .doc-title {
        text-align: center;
        font-size: 14px;
        font-weight: bold;
        letter-spacing: 2px;
    }
    .info-table {
        width: 100%;
        margin-bottom: 10px;
    }
    .info-table td {
        padding: 3px 0;
    }
    .items-table {
        width: 100%;
        margin-bottom: 14px;
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
    .signature-table {
        width: 100%;
        margin-top: 24px;
    }
    .signature-table td {
        vertical-align: top;
        padding: 4px;
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
        margin-top: 20px;
        border-top: 1px solid #000;
        padding-top: 5px;
    }
</style>
</head>
<body>

    {{-- Header --}}
    <table class="header-table">
        <tr>
            <td style="width: 80px; text-align: center; vertical-align: middle;">
                <div class="logo-box">NIA<br>LOGO</div>
            </td>
            <td style="text-align: center; vertical-align: middle;">
                <div class="doc-title">REQUEST FORM</div>
            </td>
            <td style="width: 80px; text-align: center; vertical-align: middle;">
                <div class="logo-box">BAGONG<br>PILIPINAS</div>
            </td>
        </tr>
    </table>

    {{-- Department / Section Info --}}
    <table class="info-table">
        <tr>
            <td style="width: 55%;">
                <strong>DEPARTMENT:</strong> {{ $pr->requestingOffice?->name }}
            </td>
            <td style="width: 45%;">
                <strong>DATE:</strong>
                {{ $pr->submitted_at?->format('F d, Y') ?? now()->format('F d, Y') }}
            </td>
        </tr>
        <tr>
            <td>
                <strong>SECTION:</strong> {{ $pr->requestingOffice?->name }}
            </td>
            <td></td>
        </tr>
    </table>

    {{-- Items Table --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 7%;">QTY</th>
                <th style="width: 9%;">UNIT</th>
                <th style="width: 28%;">PARTICULAR</th>
                <th style="width: 13%;">ESTIMATED<br>COST</th>
                <th style="width: 13%;">TOTAL<br>COST</th>
                <th style="width: 13%;">PPMP<br>TOTAL</th>
                <th style="width: 17%;">PPMP BALANCE<br>FROM THIS REQUEST</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($pr->items as $item)
            <tr>
                <td style="text-align: center;">{{ $item->quantity }}</td>
                <td style="text-align: center;">{{ $item->unit_of_measure }}</td>
                <td>
                    {{ $item->item_description }}
                    @if ($item->specifications)
                    <br><span style="font-size: 9px; font-style: italic;">{{ $item->specifications }}</span>
                    @endif
                </td>
                <td style="text-align: right;">{{ number_format((float) $item->unit_cost, 2) }}</td>
                <td style="text-align: right;">{{ number_format((float) $item->total_cost, 2) }}</td>
                <td></td>
                <td></td>
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
                <td></td>
            </tr>
            @endfor
        </tbody>
    </table>

    {{-- Purpose --}}
    <p><strong>PURPOSE:</strong> {{ $pr->purpose }}</p>

    {{-- Signatures --}}
    <table class="signature-table">
        <tr>
            <td style="width: 50%;">
                <p style="margin: 0 0 40px 0;">REQUESTED BY:</p>
                <div class="sig-line">
                    {{ $pr->requester->full_name }}<br>
                    <span style="font-size: 9px;">{{ $pr->requester->role?->name ?? '' }}</span>
                </div>
            </td>
            <td style="width: 50%;">
                <p style="margin: 0 0 40px 0;">APPROVED BY:</p>
                <div class="sig-line">
                    (Division Manager, EOD)
                </div>
            </td>
        </tr>
    </table>

    {{-- Footer --}}
    <div class="footer">
        NIA13-AFD-ADM-PROP-INT-Form14 Rev.00<br>
        NIA Compound, Brgy. Bancasi, Butuan City, 8600 Philippines<br>
        Telefax Number: (085) 815 26 02 &nbsp;|&nbsp; Email: r13@nia.gov.ph &nbsp;|&nbsp; Website: caraga.nia.gov.ph &nbsp;|&nbsp; TIN: 000916415134
    </div>

</body>
</html>
