<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\AbstractOfQuotation;
use App\Models\AuditLog;
use App\Models\BacResolution;
use App\Models\CanvassResponse;
use App\Models\Category;
use App\Models\LoginLog;
use App\Models\NoticeOfAward;
use App\Models\Notification;
use App\Models\Office;
use App\Models\PrAttachment;
use App\Models\PrStatusHistory;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Rfq;
use App\Models\RfqItem;
use App\Models\Role;
use App\Models\User;
use App\Policies\AbstractOfQuotationPolicy;
use App\Policies\AuditLogPolicy;
use App\Policies\BacResolutionPolicy;
use App\Policies\CanvassResponsePolicy;
use App\Policies\CategoryPolicy;
use App\Policies\LoginLogPolicy;
use App\Policies\NoticeOfAwardPolicy;
use App\Policies\NotificationPolicy;
use App\Policies\OfficePolicy;
use App\Policies\PrAttachmentPolicy;
use App\Policies\PrStatusHistoryPolicy;
use App\Policies\PurchaseOrderItemPolicy;
use App\Policies\PurchaseOrderPolicy;
use App\Policies\PurchaseRequestItemPolicy;
use App\Policies\PurchaseRequestPolicy;
use App\Policies\RfqItemPolicy;
use App\Policies\RfqPolicy;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Throw on lazy-loaded relationships in non-production environments.
        // This surfaces N+1 issues during development before they reach production.
        Model::preventLazyLoading(! app()->isProduction());

        // Register model policies for role-based authorization.
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Office::class, OfficePolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(PurchaseRequest::class, PurchaseRequestPolicy::class);
        Gate::policy(PurchaseRequestItem::class, PurchaseRequestItemPolicy::class);
        Gate::policy(PrAttachment::class, PrAttachmentPolicy::class);
        Gate::policy(PrStatusHistory::class, PrStatusHistoryPolicy::class);
        Gate::policy(PurchaseOrder::class, PurchaseOrderPolicy::class);
        Gate::policy(PurchaseOrderItem::class, PurchaseOrderItemPolicy::class);
        Gate::policy(Rfq::class, RfqPolicy::class);
        Gate::policy(RfqItem::class, RfqItemPolicy::class);
        Gate::policy(CanvassResponse::class, CanvassResponsePolicy::class);
        Gate::policy(AbstractOfQuotation::class, AbstractOfQuotationPolicy::class);
        Gate::policy(BacResolution::class, BacResolutionPolicy::class);
        Gate::policy(NoticeOfAward::class, NoticeOfAwardPolicy::class);
        Gate::policy(Notification::class, NotificationPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::policy(LoginLog::class, LoginLogPolicy::class);
    }
}
