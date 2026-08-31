<?php

declare(strict_types=1);

use App\Http\Controllers\V1\Auth\AuthController;
use App\Http\Controllers\V1\Auth\UserController;
use App\Http\Controllers\V1\Config\CategoryController;
use App\Http\Controllers\V1\Config\OfficeController;
use App\Http\Controllers\V1\Config\RoleController;
use App\Http\Controllers\V1\DashboardController;
use App\Http\Controllers\V1\Monitoring\AuditLogController;
use App\Http\Controllers\V1\Monitoring\LoginLogController;
use App\Http\Controllers\V1\Monitoring\NotificationController;
use App\Http\Controllers\V1\Procurement\PrAttachmentController;
use App\Http\Controllers\V1\Procurement\PrStatusHistoryController;
use App\Http\Controllers\V1\Procurement\PurchaseOrderController;
use App\Http\Controllers\V1\Procurement\PurchaseOrderItemController;
use App\Http\Controllers\V1\Procurement\PurchaseRequestController;
use App\Http\Controllers\V1\Procurement\PurchaseRequestItemController;
use App\Http\Controllers\V1\Procurement\SupplierController;
use App\Http\Controllers\V1\Procurement\SupplierDocumentController;
use App\Http\Controllers\V1\Resolution\BacResolutionController;
use App\Http\Controllers\V1\Resolution\NoticeOfAwardController;
use App\Http\Controllers\V1\RFQ\AbstractOfQuotationController;
use App\Http\Controllers\V1\RFQ\CanvassResponseController;
use App\Http\Controllers\V1\RFQ\RfqController;
use App\Http\Controllers\V1\RFQ\RfqItemController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {

    // -------------------------------------------------------------------------
    // Auth endpoints — unauthenticated (login is public, rate-limited)
    // -------------------------------------------------------------------------
    Route::prefix('auth')->name('auth.')->group(function (): void {
        // throttle:5,5 = 5 attempts per 5 minutes per IP (security.md rate-limit table)
        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:5,5')
            ->name('login');

        Route::post('register', [AuthController::class, 'register'])
            ->middleware('throttle:5,5')
            ->name('register');

        Route::middleware('auth:sanctum')->group(function (): void {
            // Blueprint auth contract: DELETE /api/v1/auth/logout — revokes the current token only.
            Route::delete('logout', [AuthController::class, 'logout'])->name('logout');
            Route::get('me', [AuthController::class, 'me'])->name('me');
        });
    });

    // -------------------------------------------------------------------------
    // All remaining routes are protected by Sanctum token auth
    // -------------------------------------------------------------------------
    Route::middleware('auth:sanctum')->group(function (): void {

        // ---------------------------------------------------------------------
        // Config domain
        // Route names use snake_case.dots per conventions.md and style.md
        // ---------------------------------------------------------------------
        Route::apiResource('roles', RoleController::class)
            ->names('roles');

        Route::apiResource('offices', OfficeController::class)
            ->names('offices');

        Route::apiResource('categories', CategoryController::class)
            ->names('categories');

        // ---------------------------------------------------------------------
        // Auth domain — User management
        // ---------------------------------------------------------------------
        Route::apiResource('users', UserController::class)
            ->names('users');

        // ---------------------------------------------------------------------
        // Dashboard — analytics and KPI summary, accessible to all roles
        // ---------------------------------------------------------------------
        Route::get('dashboard', [DashboardController::class, 'index'])
            ->name('dashboard.index');

        // ---------------------------------------------------------------------
        // Procurement domain
        // ---------------------------------------------------------------------
        Route::apiResource('purchase-requests', PurchaseRequestController::class)
            ->names([
                'index' => 'purchase_requests.index',
                'store' => 'purchase_requests.store',
                'show' => 'purchase_requests.show',
                'update' => 'purchase_requests.update',
                'destroy' => 'purchase_requests.destroy',
            ]);

        Route::apiResource('purchase-request-items', PurchaseRequestItemController::class)
            ->names([
                'index' => 'purchase_request_items.index',
                'store' => 'purchase_request_items.store',
                'show' => 'purchase_request_items.show',
                'update' => 'purchase_request_items.update',
                'destroy' => 'purchase_request_items.destroy',
            ]);

        Route::apiResource('pr-attachments', PrAttachmentController::class)
            ->names([
                'index' => 'pr_attachments.index',
                'store' => 'pr_attachments.store',
                'show' => 'pr_attachments.show',
                'update' => 'pr_attachments.update',
                'destroy' => 'pr_attachments.destroy',
            ]);

        // Secure, authorized file download — never served from the public disk.
        Route::get('pr-attachments/{prAttachment}/download', [PrAttachmentController::class, 'download'])
            ->name('pr_attachments.download');

        // PDF document downloads — streamed from the server, require authorization.
        Route::get('purchase-requests/{purchaseRequest}/download/request-form', [PurchaseRequestController::class, 'downloadRequestForm'])
            ->name('purchase_requests.download.request_form');

        Route::get('purchase-requests/{purchaseRequest}/download/purchase-request', [PurchaseRequestController::class, 'downloadPurchaseRequest'])
            ->name('purchase_requests.download.purchase_request');

        Route::get('purchase-orders/{purchaseOrder}/download/purchase-order', [PurchaseOrderController::class, 'downloadPurchaseOrder'])
            ->name('purchase_orders.download.purchase_order');

        // PR status history is read-only (append-only audit log)
        Route::get('pr-status-histories', [PrStatusHistoryController::class, 'index'])
            ->name('pr_status_histories.index');
        Route::get('pr-status-histories/{prStatusHistory}', [PrStatusHistoryController::class, 'show'])
            ->name('pr_status_histories.show');

        Route::apiResource('suppliers', SupplierController::class)
            ->names([
                'index' => 'suppliers.index',
                'store' => 'suppliers.store',
                'show' => 'suppliers.show',
                'update' => 'suppliers.update',
                'destroy' => 'suppliers.destroy',
            ]);

        // Secure, authorized supplier logo download — never served from the public disk.
        Route::get('suppliers/{supplier}/logo', [SupplierController::class, 'downloadLogo'])
            ->name('suppliers.download_logo');

        // Supplier documents: no update endpoint — delete and re-upload instead.
        Route::apiResource('supplier-documents', SupplierDocumentController::class)
            ->except(['update'])
            ->names([
                'index' => 'supplier_documents.index',
                'store' => 'supplier_documents.store',
                'show' => 'supplier_documents.show',
                'destroy' => 'supplier_documents.destroy',
            ]);

        // Secure, authorized supplier document download — never served from the public disk.
        Route::get('supplier-documents/{supplierDocument}/download', [SupplierDocumentController::class, 'download'])
            ->name('supplier_documents.download');

        Route::apiResource('purchase-orders', PurchaseOrderController::class)
            ->names([
                'index' => 'purchase_orders.index',
                'store' => 'purchase_orders.store',
                'show' => 'purchase_orders.show',
                'update' => 'purchase_orders.update',
                'destroy' => 'purchase_orders.destroy',
            ]);

        Route::apiResource('purchase-order-items', PurchaseOrderItemController::class)
            ->names([
                'index' => 'purchase_order_items.index',
                'store' => 'purchase_order_items.store',
                'show' => 'purchase_order_items.show',
                'update' => 'purchase_order_items.update',
                'destroy' => 'purchase_order_items.destroy',
            ]);

        // ---------------------------------------------------------------------
        // RFQ domain
        // ---------------------------------------------------------------------
        Route::apiResource('rfqs', RfqController::class)
            ->names('rfqs');

        // Secure, authorized RFQ document download — never served from the public disk.
        Route::get('rfqs/{rfq}/download', [RfqController::class, 'download'])
            ->name('rfqs.download');

        Route::apiResource('rfq-items', RfqItemController::class)
            ->names([
                'index' => 'rfq_items.index',
                'store' => 'rfq_items.store',
                'show' => 'rfq_items.show',
                'update' => 'rfq_items.update',
                'destroy' => 'rfq_items.destroy',
            ]);

        Route::apiResource('canvass-responses', CanvassResponseController::class)
            ->names([
                'index' => 'canvass_responses.index',
                'store' => 'canvass_responses.store',
                'show' => 'canvass_responses.show',
                'update' => 'canvass_responses.update',
                'destroy' => 'canvass_responses.destroy',
            ]);

        Route::apiResource('abstracts-of-quotation', AbstractOfQuotationController::class)
            ->parameters(['abstracts-of-quotation' => 'abstract_of_quotation'])
            ->names([
                'index' => 'abstracts_of_quotation.index',
                'store' => 'abstracts_of_quotation.store',
                'show' => 'abstracts_of_quotation.show',
                'update' => 'abstracts_of_quotation.update',
                'destroy' => 'abstracts_of_quotation.destroy',
            ]);

        // Secure, authorized abstract of quotation document download.
        Route::get('abstracts-of-quotation/{abstract_of_quotation}/download', [AbstractOfQuotationController::class, 'download'])
            ->name('abstracts_of_quotation.download');

        // ---------------------------------------------------------------------
        // Resolution domain
        // ---------------------------------------------------------------------
        Route::apiResource('bac-resolutions', BacResolutionController::class)
            ->names([
                'index' => 'bac_resolutions.index',
                'store' => 'bac_resolutions.store',
                'show' => 'bac_resolutions.show',
                'update' => 'bac_resolutions.update',
                'destroy' => 'bac_resolutions.destroy',
            ]);

        // Secure, authorized BAC resolution document download.
        Route::get('bac-resolutions/{bacResolution}/download', [BacResolutionController::class, 'download'])
            ->name('bac_resolutions.download');

        Route::apiResource('notices-of-award', NoticeOfAwardController::class)
            ->parameters(['notices-of-award' => 'notice_of_award'])
            ->names([
                'index' => 'notices_of_award.index',
                'store' => 'notices_of_award.store',
                'show' => 'notices_of_award.show',
                'update' => 'notices_of_award.update',
                'destroy' => 'notices_of_award.destroy',
            ]);

        // Secure, authorized notice of award document download.
        Route::get('notices-of-award/{notice_of_award}/download', [NoticeOfAwardController::class, 'download'])
            ->name('notices_of_award.download');

        // ---------------------------------------------------------------------
        // Monitoring domain
        // ---------------------------------------------------------------------

        // Notifications: no store/update; index + show + mark-read only
        Route::get('notifications', [NotificationController::class, 'index'])
            ->name('notifications.index');
        Route::get('notifications/{notification}', [NotificationController::class, 'show'])
            ->name('notifications.show');
        Route::patch('notifications/{notification}/mark-read', [NotificationController::class, 'markRead'])
            ->name('notifications.mark_read');

        // Audit logs: read-only (append-only)
        Route::get('audit-logs', [AuditLogController::class, 'index'])
            ->name('audit_logs.index');
        Route::get('audit-logs/{auditLog}', [AuditLogController::class, 'show'])
            ->name('audit_logs.show');

        // Login logs: read-only (append-only)
        Route::get('login-logs', [LoginLogController::class, 'index'])
            ->name('login_logs.index');
        Route::get('login-logs/{loginLog}', [LoginLogController::class, 'show'])
            ->name('login_logs.show');
    });
});
