<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect(route('dashboard'));
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('bookings.index'))->assertRedirect(route('login'));
    }

    public function test_registration_is_disabled(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_admin_can_access_admin_pages_but_not_approvals(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('bookings.index'))->assertOk();
        $this->actingAs($admin)->get(route('bookings.create'))->assertOk();
        $this->actingAs($admin)->get(route('reports.index'))->assertOk();
        $this->actingAs($admin)->get(route('activity-logs.index'))->assertOk();
        $this->actingAs($admin)->get(route('approvals.index'))->assertForbidden();
    }

    public function test_approver_can_access_approvals_but_not_admin_pages(): void
    {
        $approver = User::factory()->approver()->create();

        $this->actingAs($approver)->get(route('dashboard'))->assertOk();
        $this->actingAs($approver)->get(route('approvals.index'))->assertOk();
        $this->actingAs($approver)->get(route('bookings.index'))->assertForbidden();
        $this->actingAs($approver)->get(route('bookings.create'))->assertForbidden();
        $this->actingAs($approver)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($approver)->get(route('activity-logs.index'))->assertForbidden();
    }
}
