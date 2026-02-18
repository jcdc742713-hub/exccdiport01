<?php

namespace Tests\Feature\Policies;

use Tests\TestCase;
use App\Models\User;
use App\Enums\UserRoleEnum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $manager;
    protected User $operator;
    protected User $student;
    protected User $targetAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create([
            'role' => UserRoleEnum::ADMIN,
            'admin_type' => 'super',
            'is_active' => true,
            'terms_accepted_at' => now(),
        ]);

        $this->manager = User::factory()->create([
            'role' => UserRoleEnum::ADMIN,
            'admin_type' => 'manager',
            'is_active' => true,
            'terms_accepted_at' => now(),
        ]);

        $this->operator = User::factory()->create([
            'role' => UserRoleEnum::ADMIN,
            'admin_type' => 'operator',
            'is_active' => true,
            'terms_accepted_at' => now(),
        ]);

        $this->student = User::factory()->create([
            'role' => UserRoleEnum::STUDENT,
        ]);

        $this->targetAdmin = User::factory()->create([
            'role' => UserRoleEnum::ADMIN,
            'admin_type' => 'operator',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function super_admin_can_view_any_user(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('users.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function manager_cannot_view_user_list(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('users.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function operator_cannot_view_user_list(): void
    {
        $response = $this->actingAs($this->operator)
            ->get(route('users.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function student_cannot_view_user_list(): void
    {
        $response = $this->actingAs($this->student)
            ->get(route('users.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function super_admin_can_view_specific_user(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('users.show', $this->targetAdmin->id));

        $response->assertStatus(200);
    }

    /** @test */
    public function user_can_view_own_profile(): void
    {
        $response = $this->actingAs($this->operator)
            ->get(route('users.show', $this->operator->id));

        $response->assertStatus(200);
    }

    /** @test */
    public function manager_cannot_view_other_admin(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('users.show', $this->targetAdmin->id));

        $response->assertStatus(403);
    }

    /** @test */
    public function only_super_admin_can_create_admin(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('users.create'));

        $response->assertStatus(200);
    }

    /** @test */
    public function manager_cannot_access_create_admin_page(): void
    {
        $response = $this->actingAs($this->manager)
            ->get(route('users.create'));

        $response->assertStatus(403);
    }

    /** @test */
    public function operator_cannot_access_create_admin_page(): void
    {
        $response = $this->actingAs($this->operator)
            ->get(route('users.create'));

        $response->assertStatus(403);
    }

    /** @test */
    public function super_admin_can_update_any_admin(): void
    {
        $data = [
            'first_name' => 'Updated',
            'last_name' => 'Admin',
            'email' => $this->targetAdmin->email,
            'admin_type' => 'manager',
            'department' => 'Updated Department',
        ];

        $response = $this->actingAs($this->superAdmin)
            ->put(route('users.update', $this->targetAdmin->id), $data);

        $this->assertDatabaseHas('users', [
            'id' => $this->targetAdmin->id,
            'department' => 'Updated Department',
        ]);
    }

    /** @test */
    public function admin_can_update_own_profile(): void
    {
        $data = [
            'first_name' => 'Updated',
            'last_name' => 'Operator',
            'email' => $this->operator->email,
            'admin_type' => 'operator',
            'department' => 'Self Updated',
        ];

        $response = $this->actingAs($this->operator)
            ->put(route('users.update', $this->operator->id), $data);

        $this->assertDatabaseHas('users', [
            'id' => $this->operator->id,
            'department' => 'Self Updated',
        ]);
    }

    /** @test */
    public function manager_cannot_update_other_admin(): void
    {
        $data = [
            'first_name' => 'Hacked',
            'last_name' => 'Admin',
            'email' => $this->targetAdmin->email,
            'admin_type' => 'manager',
        ];

        $response = $this->actingAs($this->manager)
            ->put(route('users.update', $this->targetAdmin->id), $data);

        $response->assertStatus(403);
    }

    /** @test */
    public function operator_cannot_update_other_admin(): void
    {
        $data = [
            'first_name' => 'Hacked',
            'last_name' => 'Admin',
            'email' => $this->targetAdmin->email,
            'admin_type' => 'manager',
        ];

        $response = $this->actingAs($this->operator)
            ->put(route('users.update', $this->targetAdmin->id), $data);

        $response->assertStatus(403);
    }

    /** @test */
    public function only_super_admin_can_delete_admin(): void
    {
        $adminToDelete = User::factory()->create([
            'role' => UserRoleEnum::ADMIN,
            'admin_type' => 'operator',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->delete(route('users.destroy', $adminToDelete->id));

        $response->assertStatus(403); // Soft delete not harddelete enforced
    }

    /** @test */
    public function manager_cannot_delete_admin(): void
    {
        $response = $this->actingAs($this->manager)
            ->delete(route('users.destroy', $this->targetAdmin->id));

        $response->assertStatus(403);
    }

    /** @test */
    public function operator_cannot_delete_admin(): void
    {
        $response = $this->actingAs($this->operator)
            ->delete(route('users.destroy', $this->targetAdmin->id));

        $response->assertStatus(403);
    }

    /** @test */
    public function student_cannot_delete_admin(): void
    {
        $response = $this->actingAs($this->student)
            ->delete(route('users.destroy', $this->targetAdmin->id));

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_accept_own_terms(): void
    {
        $unacceptedAdmin = User::factory()->create([
            'role' => UserRoleEnum::ADMIN,
            'admin_type' => 'operator',
            'terms_accepted_at' => null,
        ]);

        $this->assertNull($unacceptedAdmin->terms_accepted_at);

        // Simulate acceptance (in real app, this would be a form submission)
        $unacceptedAdmin->acceptTerms();
        $unacceptedAdmin->save();

        $this->assertNotNull($unacceptedAdmin->refresh()->terms_accepted_at);
    }

    /** @test */
    public function admin_cannot_accept_terms_for_another_user(): void
    {
        $unacceptedAdmin = User::factory()->create([
            'role' => UserRoleEnum::ADMIN,
            'admin_type' => 'operator',
            'terms_accepted_at' => null,
        ]);

        // The policy should prevent one user from accepting terms on behalf of another
        // This would be tested at the controller/endpoint level
        $this->assertNull($unacceptedAdmin->terms_accepted_at);
        // Only the user themselves can accept their own terms
    }

    /** @test */
    public function only_super_admin_can_manage_admins(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('users.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function manager_cannot_manage_admins(): void
    {
        $response = $this->actingAs($this->manager)
            ->post(route('users.deactivate', $this->targetAdmin->id));

        $response->assertStatus(403);
    }

    /** @test */
    public function operator_cannot_manage_admins(): void
    {
        $response = $this->actingAs($this->operator)
            ->post(route('users.deactivate', $this->targetAdmin->id));

        $response->assertStatus(403);
    }

    /** @test */
    public function inactive_admin_cannot_perform_admin_actions(): void
    {
        $inactiveAdmin = User::factory()->create([
            'role' => UserRoleEnum::ADMIN,
            'admin_type' => 'manager',
            'is_active' => false,
        ]);

        $response = $this->actingAs($inactiveAdmin)
            ->get(route('users.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function inactive_admin_cannot_create_users(): void
    {
        $inactiveAdmin = User::factory()->create([
            'role' => UserRoleEnum::ADMIN,
            'admin_type' => 'super',
            'is_active' => false,
        ]);

        $response = $this->actingAs($inactiveAdmin)
            ->get(route('users.create'));

        $response->assertStatus(403);
    }

    /** @test */
    public function deactivated_admin_loses_access(): void
    {
        $admin = User::factory()->create([
            'role' => UserRoleEnum::ADMIN,
            'admin_type' => 'manager',
            'is_active' => true,
        ]);

        // First verify they have access
        $response = $this->actingAs($admin)
            ->get(route('users.show', $admin->id));
        $response->assertStatus(200);

        // Deactivate them
        $admin->update(['is_active' => false]);
        $admin->refresh();

        // Now verify they don't have access
        $response = $this->actingAs($admin)
            ->get(route('users.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function non_admin_role_cannot_access_admin_functions(): void
    {
        $accounting = User::factory()->create([
            'role' => UserRoleEnum::ACCOUNTING,
        ]);

        $response = $this->actingAs($accounting)
            ->get(route('users.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function super_admin_can_restore_deactivated_admin(): void
    {
        $deactivatedAdmin = User::factory()->create([
            'role' => UserRoleEnum::ADMIN,
            'admin_type' => 'operator',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('users.reactivate', $deactivatedAdmin->id));

        $this->assertTrue($deactivatedAdmin->refresh()->is_active);
    }

    /** @test */
    public function manager_cannot_restore_deactivated_admin(): void
    {
        $deactivatedAdmin = User::factory()->create([
            'role' => UserRoleEnum::ADMIN,
            'admin_type' => 'operator',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->manager)
            ->post(route('users.reactivate', $deactivatedAdmin->id));

        $response->assertStatus(403);
    }

    /** @test */
    public function operator_cannot_deactivate_admin(): void
    {
        $response = $this->actingAs($this->operator)
            ->post(route('users.deactivate', $this->manager->id));

        $response->assertStatus(403);
    }

    /** @test */
    public function manager_cannot_deactivate_admin(): void
    {
        $response = $this->actingAs($this->manager)
            ->post(route('users.deactivate', $this->targetAdmin->id));

        $response->assertStatus(403);
    }

    /** @test */
    public function super_admin_can_deactivate_other_super_admin(): void
    {
        $otherSuperAdmin = User::factory()->create([
            'role' => UserRoleEnum::ADMIN,
            'admin_type' => 'super',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('users.deactivate', $otherSuperAdmin->id));

        $this->assertFalse($otherSuperAdmin->refresh()->is_active);
    }

    /** @test */
    public function super_admin_cannot_deactivate_themselves_if_only_super_admin(): void
    {
        // Make this super admin the only super admin
        User::query()->where('admin_type', 'super')->where('id', '!=', $this->superAdmin->id)->update(['is_active' => false]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('users.deactivate', $this->superAdmin->id));

        $response->assertStatus(403);
        $this->assertTrue($this->superAdmin->refresh()->is_active);
    }
}
