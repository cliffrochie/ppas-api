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
        margin-bottom: 8px;
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

    <div class="doc-title">PURCHASE ORDER</div>

    {{-- PO Info --}}
    <table class="info-table">
        <tr>
            <td style="width: 60%;">
                <strong>Supplier:</strong>
                {{ $po->supplier?->name ?? $po->supplier_name }}
            </td>
            <td style="width: 40%;">
                <strong>P.O. No.:</strong> {{ $po->po_number }}
            </td>
        </tr>
        <tr>
            <td>
                <strong>Address:</strong>
                {{ $po->supplier?->address_city ?? $po->supplier_address }}
            </td>
            <td>
                <strong>Date:</strong> {{ $po->created_at->format('F d, Y') }}
            </td>
        </tr>
        <tr>
            <td>
                <strong>T.I.N.:</strong> {{ $po->supplier?->tin_number ?? '' }}
            </td>
            <td>
                <strong>P.R. No.:</strong> {{ $po->purchaseRequest->pr_number }}
            </td>
        </tr>
        <tr>
            <td>
                <strong>Mode of Procurement:</strong> SHOPPING
            </td>
            <td>
                <strong>Date:</strong>
                {{ $po->purchaseRequest->submitted_at?->format('F d, Y') }}
            </td>
        </tr>
    </table>

    <p style="margin: 6px 0;">
        Gentlemen:<br>
        Please furnish this office the following articles subject to the terms and conditions contained herein.
    </p>

    {{-- Delivery / Payment Terms --}}
    <table class="info-table" style="margin-bottom: 10px;">
        <tr>
            <td style="width: 50%;">
                <strong>Place of Delivery:</strong> NIA-REGIONAL OFFICE 13
            </td>
            <td style="width: 50%;">
                <strong>Delivery Term:</strong> {{ $po->delivery_terms ?? 'COD' }}
            </td>
        </tr>
        <tr>
            <td>
                <strong>Date of Delivery:</strong>
                {{ $po->delivery_date?->format('F d, Y') ?? 'UPON RECEIPT OF PAYMENT' }}
            </td>
            <td>
                <strong>Payment Term:</strong> {{ $po->payment_terms ?? 'COD' }}
            </td>
        </tr>
    </table>

    {{-- Items Table --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 7%;">Item<br>No.</th>
                <th style="width: 11%;">Quantity</th>
                <th style="width: 9%;">Unit</th>
                <th style="width: 40%;">Description</th>
                <th style="width: 16%;">Unit Cost</th>
                <th style="width: 17%;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($po->items as $index => $item)
            <tr>
                <td style="text-align: center;">{{ $index + 1 }}</td>
                <td style="text-align: center;">{{ $item->quantity }}</td>
                <td style="text-align: center;">{{ $item->unit_of_measure }}</td>
                <td>{{ $item->item_description }}</td>
                <td style="text-align: right;">{{ number_format((float) $item->unit_cost, 2) }}</td>
                <td style="text-align: right;">{{ number_format((float) $item->total_cost, 2) }}</td>
            </tr>
            @endforeach
            {{-- Empty rows to pad the table for handwriting --}}
            @for ($i = 0; $i < max(0, 5 - $po->items->count()); $i++)
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

    <p><strong>PURPOSE:</strong> {{ $po->purchaseRequest->purpose }}</p>

    <p style="text-align: right;">
        <strong>TOTAL: P {{ number_format((float) $po->total_amount, 2) }}</strong>
    </p>

    <p style="font-size: 9px; margin: 4px 0;">
        In case of failure to make the full delivery within the time specified above, a penalty of one-tenth (1/10) of
        one percent for every day of delay shall be imposed.
    </p>

    {{-- Conforme / Signature --}}
    <table style="width: 100%; margin-top: 20px;">
        <tr>
            <td style="width: 50%; vertical-align: top;">
                <strong>Conforme:</strong>
                <div class="sig-line" style="margin-top: 28px;">
                    (Signature over printed name)<br>
                    {{ $po->created_at->format('m-d-y') }}
                </div>
            </td>
            <td style="width: 50%; text-align: center; vertical-align: top;">
                Very truly yours,
                <div class="sig-line" style="margin-top: 28px;">
                    Regional Manager A
                </div>
            </td>
        </tr>
    </table>

    {{-- Fund Availability --}}
    <table style="width: 100%; margin-top: 16px; border-top: 1px solid #000; padding-top: 6px;">
        <tr>
            <td style="width: 55%; vertical-align: top; padding-top: 6px;">
                Funds Available: _______________&nbsp;&nbsp;
                Amount: P {{ number_format((float) $po->total_amount, 2) }}<br>
                ALOBS No.: {{ $po->purchaseRequest->alobs_number ?? '_______________' }}
            </td>
            <td style="width: 45%; text-align: center; vertical-align: top;">
                <div class="sig-line" style="margin-top: 28px;">
                    (Chief Corporate Accountant B)
                </div>
            </td>
        </tr>
    </table>

    {{-- Footer --}}
    <div class="footer">
        NIA13-AFD-ADM-PROP-EXT-Form12 Rev.00<br>
        NIA Compound, Brgy. Bancasi, Butuan City, 8600 Philippines<br>
        Telefax Number: (085) 815 26 02 &nbsp;|&nbsp; Email: r13@nia.gov.ph &nbsp;|&nbsp; Website: caraga.nia.gov.ph &nbsp;|&nbsp; TIN: 000916415134
    </div>

</body>
</html>
