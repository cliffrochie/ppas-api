<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\CanvassResponse;
use App\Models\Category;
use App\Models\Notification;
use App\Models\Office;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\Rfq;
use App\Models\RfqItem;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Tests\TestCase;

final class SortingTest extends TestCase
{
    private function procurementOfficer(): User
    {
        $role = Role::where('name', 'procurement_officer')->firstOrFail();

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function requesterUser(): User
    {
        $role = Role::where('name', 'requester')->firstOrFail();

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_categories_can_be_sorted_asc_and_desc(): void
    {
        Category::query()->delete();

        Category::create(['name' => 'Zebra Supplies', 'is_active' => true]);
        Category::create(['name' => 'Apple Supplies', 'is_active' => true]);
        Category::create(['name' => 'Mango Supplies', 'is_active' => true]);

        $user = $this->procurementOfficer();

        // Sort by name ASC
        $responseAsc = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/categories?sort_by=name&sort_order=asc')
            ->assertStatus(200);

        $namesAsc = collect($responseAsc->json('data'))->pluck('name')->all();
        $this->assertSame(['Apple Supplies', 'Mango Supplies', 'Zebra Supplies'], $namesAsc);

        // Sort by name DESC
        $responseDesc = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/categories?sort_by=name&sort_order=desc')
            ->assertStatus(200);

        $namesDesc = collect($responseDesc->json('data'))->pluck('name')->all();
        $this->assertSame(['Zebra Supplies', 'Mango Supplies', 'Apple Supplies'], $namesDesc);
    }

    public function test_offices_can_be_sorted(): void
    {
        Office::query()->delete();

        Office::create(['name' => 'Office C', 'code' => 'OFF-C']);
        Office::create(['name' => 'Office A', 'code' => 'OFF-A']);
        Office::create(['name' => 'Office B', 'code' => 'OFF-B']);

        $user = $this->procurementOfficer();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/offices?sort_by=code&sort_order=asc')
            ->assertStatus(200);

        $codes = collect($response->json('data'))->pluck('code')->all();
        $this->assertSame(['OFF-A', 'OFF-B', 'OFF-C'], $codes);
    }

    public function test_users_can_be_sorted_by_email_and_name(): void
    {
        $role = Role::where('name', 'requester')->firstOrFail();
        $office = Office::firstOrFail();

        User::create([
            'first_name' => 'Alice',
            'last_name' => 'Zeta',
            'email' => 'alice@example.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'office_id' => $office->id,
            'is_active' => true,
        ]);
        User::create([
            'first_name' => 'Bob',
            'last_name' => 'Alpha',
            'email' => 'bob@example.com',
            'password' => bcrypt('password'),
            'role_id' => $role->id,
            'office_id' => $office->id,
            'is_active' => true,
        ]);

        $officer = $this->procurementOfficer();

        $response = $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/users?sort_by=last_name&sort_order=asc')
            ->assertStatus(200);

        $lastNames = collect($response->json('data'))->pluck('last_name')->all();
        $this->assertContains('Alpha', $lastNames);
        $this->assertContains('Zeta', $lastNames);
        $alphaIndex = array_search('Alpha', $lastNames, true);
        $zetaIndex = array_search('Zeta', $lastNames, true);
        $this->assertLessThan($zetaIndex, $alphaIndex);
    }

    public function test_purchase_requests_can_be_sorted_by_total_amount(): void
    {
        $requester = $this->requesterUser();
        $office = Office::firstOrFail();

        PurchaseRequest::create([
            'requester_id' => $requester->id,
            'requesting_office_id' => $office->id,
            'purpose' => 'Low cost items',
            'status' => 'draft',
            'total_amount' => 100.00,
        ]);
        PurchaseRequest::create([
            'requester_id' => $requester->id,
            'requesting_office_id' => $office->id,
            'purpose' => 'High cost items',
            'status' => 'draft',
            'total_amount' => 5000.00,
        ]);
        PurchaseRequest::create([
            'requester_id' => $requester->id,
            'requesting_office_id' => $office->id,
            'purpose' => 'Medium cost items',
            'status' => 'draft',
            'total_amount' => 1500.00,
        ]);

        // Ascending
        $responseAsc = $this->actingAs($requester, 'sanctum')
            ->getJson('/api/v1/purchase-requests?sort_by=total_amount&sort_order=asc')
            ->assertStatus(200);

        $amountsAsc = collect($responseAsc->json('data'))->pluck('total_amount')->map(fn ($v) => (float) $v)->all();
        $this->assertSame([100.0, 1500.0, 5000.0], $amountsAsc);

        // Descending
        $responseDesc = $this->actingAs($requester, 'sanctum')
            ->getJson('/api/v1/purchase-requests?sort_by=total_amount&sort_order=desc')
            ->assertStatus(200);

        $amountsDesc = collect($responseDesc->json('data'))->pluck('total_amount')->map(fn ($v) => (float) $v)->all();
        $this->assertSame([5000.0, 1500.0, 100.0], $amountsDesc);
    }

    public function test_suppliers_can_be_sorted_by_name(): void
    {
        Supplier::query()->delete();

        Supplier::create([
            'name' => 'Zulu Supplies',
            'email' => 'zulu@supplies.com',
            'tin_number' => '000-000-003-000',
            'is_active' => true,
        ]);
        Supplier::create([
            'name' => 'Alpha Supplies',
            'email' => 'alpha@supplies.com',
            'tin_number' => '000-000-001-000',
            'is_active' => true,
        ]);
        Supplier::create([
            'name' => 'Bravo Supplies',
            'email' => 'bravo@supplies.com',
            'tin_number' => '000-000-002-000',
            'is_active' => true,
        ]);

        $officer = $this->procurementOfficer();

        $response = $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/suppliers?sort_by=name&sort_order=asc')
            ->assertStatus(200);

        $names = collect($response->json('data'))->pluck('name')->all();
        $this->assertSame(['Alpha Supplies', 'Bravo Supplies', 'Zulu Supplies'], $names);
    }

    public function test_purchase_orders_can_be_sorted_by_total_amount(): void
    {
        $officer = $this->procurementOfficer();
        $office = Office::firstOrFail();
        $supplier = Supplier::create(['name' => 'Supp', 'email' => 's@s.com', 'is_active' => true]);

        $pr1 = PurchaseRequest::create([
            'requester_id' => $officer->id,
            'requesting_office_id' => $office->id,
            'purpose' => 'PO test 1',
            'status' => 'submitted',
            'total_amount' => 3000.00,
        ]);
        $pr2 = PurchaseRequest::create([
            'requester_id' => $officer->id,
            'requesting_office_id' => $office->id,
            'purpose' => 'PO test 2',
            'status' => 'submitted',
            'total_amount' => 3000.00,
        ]);

        PurchaseOrder::create([
            'po_number' => 'PO-2026-00001',
            'purchase_request_id' => $pr1->id,
            'prepared_by_id' => $officer->id,
            'supplier_id' => $supplier->id,
            'supplier_name' => 'Supp',
            'total_amount' => 100.00,
            'status' => 'draft',
        ]);
        PurchaseOrder::create([
            'po_number' => 'PO-2026-00002',
            'purchase_request_id' => $pr2->id,
            'prepared_by_id' => $officer->id,
            'supplier_id' => $supplier->id,
            'supplier_name' => 'Supp',
            'total_amount' => 500.00,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/purchase-orders?sort_by=total_amount&sort_order=desc')
            ->assertStatus(200);

        $amounts = collect($response->json('data'))->pluck('total_amount')->map(fn ($v) => (float) $v)->all();
        $this->assertSame([500.0, 100.0], $amounts);
    }

    public function test_rfqs_can_be_sorted_by_deadline(): void
    {
        $officer = $this->procurementOfficer();
        $office = Office::firstOrFail();

        $pr1 = PurchaseRequest::create([
            'requester_id' => $officer->id,
            'requesting_office_id' => $office->id,
            'purpose' => 'RFQ test 1',
            'status' => 'submitted',
            'total_amount' => 3000.00,
        ]);
        $pr2 = PurchaseRequest::create([
            'requester_id' => $officer->id,
            'requesting_office_id' => $office->id,
            'purpose' => 'RFQ test 2',
            'status' => 'submitted',
            'total_amount' => 3000.00,
        ]);

        Rfq::create([
            'rfq_number' => 'RFQ-2026-00001',
            'purchase_request_id' => $pr1->id,
            'prepared_by_id' => $officer->id,
            'deadline' => '2026-10-15',
            'status' => 'draft',
        ]);
        Rfq::create([
            'rfq_number' => 'RFQ-2026-00002',
            'purchase_request_id' => $pr2->id,
            'prepared_by_id' => $officer->id,
            'deadline' => '2026-09-01',
            'status' => 'draft',
        ]);

        $response = $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/rfqs?sort_by=deadline&sort_order=asc')
            ->assertStatus(200);

        $deadlines = collect($response->json('data'))->pluck('deadline')->all();
        $this->assertSame(['2026-09-01T00:00:00.000000Z', '2026-10-15T00:00:00.000000Z'], $deadlines);
    }

    public function test_canvass_responses_can_be_sorted(): void
    {
        $officer = $this->procurementOfficer();
        $office = Office::firstOrFail();

        $pr = PurchaseRequest::create([
            'requester_id' => $officer->id,
            'requesting_office_id' => $office->id,
            'purpose' => 'Canvass test',
            'status' => 'submitted',
            'total_amount' => 3000.00,
        ]);
        $prItem = PurchaseRequestItem::create([
            'purchase_request_id' => $pr->id,
            'item_description' => 'Item 1',
            'quantity' => 10,
            'unit_of_measure' => 'pcs',
            'unit_cost' => 50,
            'total_cost' => 500,
        ]);
        $rfq = Rfq::create([
            'rfq_number' => 'RFQ-2026-00003',
            'purchase_request_id' => $pr->id,
            'prepared_by_id' => $officer->id,
            'deadline' => '2026-10-15',
            'status' => 'draft',
        ]);
        $rfqItem = RfqItem::create([
            'rfq_id' => $rfq->id,
            'pr_item_id' => $prItem->id,
            'item_description' => 'Item 1',
            'quantity' => 10,
            'unit_of_measure' => 'pcs',
        ]);

        CanvassResponse::create([
            'rfq_id' => $rfq->id,
            'rfq_item_id' => $rfqItem->id,
            'supplier_name' => 'Supplier High',
            'unit_price' => 100.00,
            'total_price' => 1000.00,
        ]);
        CanvassResponse::create([
            'rfq_id' => $rfq->id,
            'rfq_item_id' => $rfqItem->id,
            'supplier_name' => 'Supplier Low',
            'unit_price' => 50.00,
            'total_price' => 500.00,
        ]);

        $response = $this->actingAs($officer, 'sanctum')
            ->getJson('/api/v1/canvass-responses?sort_by=unit_price&sort_order=asc')
            ->assertStatus(200);

        $prices = collect($response->json('data'))->pluck('unit_price')->map(fn ($v) => (float) $v)->all();
        $this->assertSame([50.0, 100.0], $prices);
    }

    public function test_notifications_can_be_sorted_by_title(): void
    {
        $requester = $this->requesterUser();

        Notification::create([
            'user_id' => $requester->id,
            'type' => 'status_change',
            'title' => 'Zebra Alert',
            'message' => 'Test message',
            'is_read' => false,
        ]);
        Notification::create([
            'user_id' => $requester->id,
            'type' => 'status_change',
            'title' => 'Alpha Alert',
            'message' => 'Test message',
            'is_read' => false,
        ]);

        $response = $this->actingAs($requester, 'sanctum')
            ->getJson('/api/v1/notifications?sort_by=title&sort_order=asc')
            ->assertStatus(200);

        $titles = collect($response->json('data'))->pluck('title')->all();
        $this->assertSame(['Alpha Alert', 'Zebra Alert'], $titles);
    }

    public function test_invalid_sort_by_returns_422(): void
    {
        $user = $this->procurementOfficer();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/categories?sort_by=invalid_column_name')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sort_by']);
    }

    public function test_invalid_sort_order_returns_422(): void
    {
        $user = $this->procurementOfficer();

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/categories?sort_by=name&sort_order=invalid_order')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sort_order']);
    }
}
