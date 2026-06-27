<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

final class PdfService
{
    /**
     * Generate the NIA internal Request Form (RF) PDF.
     * Renders on legal paper to match the physical NIA13-AFD-ADM-PROP-INT-Form14 form.
     */
    public function requestForm(PurchaseRequest $pr): Response
    {
        $pr->load(['requester.role', 'requestingOffice', 'items']);

        $pdf = Pdf::loadView('pdf.request-form', ['pr' => $pr]);
        $pdf->setPaper('legal', 'portrait');

        $filename = $pr->rf_number
            ? "request-form-{$pr->rf_number}.pdf"
            : "request-form-draft.pdf";

        return $pdf->download($filename);
    }

    /**
     * Generate the official NIA Purchase Request (PR) PDF.
     * Renders on legal paper to match the physical NIA form.
     */
    public function purchaseRequest(PurchaseRequest $pr): Response
    {
        $pr->load(['requester.role', 'requestingOffice', 'items']);

        $pdf = Pdf::loadView('pdf.purchase-request', ['pr' => $pr]);
        $pdf->setPaper('legal', 'portrait');

        $filename = $pr->pr_number
            ? "purchase-request-{$pr->pr_number}.pdf"
            : "purchase-request-draft.pdf";

        return $pdf->download($filename);
    }

    /**
     * Generate the official NIA Purchase Order (PO) PDF.
     * Renders on legal paper to match the physical NIA13-AFD-ADM-PROP-EXT-Form12 form.
     */
    public function purchaseOrder(PurchaseOrder $po): Response
    {
        $po->load(['purchaseRequest.requester.role', 'purchaseRequest.requestingOffice', 'items', 'supplier']);

        $pdf = Pdf::loadView('pdf.purchase-order', ['po' => $po]);
        $pdf->setPaper('legal', 'portrait');

        $filename = $po->po_number
            ? "purchase-order-{$po->po_number}.pdf"
            : "purchase-order-draft.pdf";

        return $pdf->download($filename);
    }
}
