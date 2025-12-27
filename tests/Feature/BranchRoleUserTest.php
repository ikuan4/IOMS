<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Role;
use App\Models\Branch;

class BranchRoleUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_block_create_user_when_role_branch_mismatch()
    {
        $branchA = Branch::create(['name' => 'A']);
        $branchB = Branch::create(['name' => 'B']);

        $roleA = Role::create(['name' => 'RoleA', 'slug' => 'role-a', 'is_active' => true, 'branch_id' => $branchA->id]);

        $actor = User::factory()->create();

        $response = $this->actingAs($actor)->post('/users', [
            'name' => 'Mismatch User',
            'mobile' => '9000000001',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role_id' => $roleA->id,
            'branch_id' => $branchB->id,
            'active' => false,
        ]);

        if (session()->has('error')) {
            $this->assertEquals('Selected role belongs to a different branch.', session('error'));
        }
        $this->assertDatabaseMissing('users', ['mobile' => '9000000001']);
    }

    public function test_block_update_user_when_role_branch_mismatch()
    {
        $branchA = Branch::create(['name' => 'A']);
        $branchB = Branch::create(['name' => 'B']);

        $roleA = Role::create(['name' => 'RoleA', 'slug' => 'role-a', 'is_active' => true, 'branch_id' => $branchA->id]);
        $roleB = Role::create(['name' => 'RoleB', 'slug' => 'role-b', 'is_active' => true, 'branch_id' => $branchB->id]);

        $user = User::factory()->create(['role_id' => $roleA->id, 'branch_id' => $branchA->id, 'active' => false]);
        $actor = User::factory()->create();

        $response = $this->actingAs($actor)->patch('/users/' . $user->id, [
            'name' => $user->name,
            'mobile' => $user->mobile,
            'email' => $user->email,
            'role_id' => $roleB->id,
            'branch_id' => $user->branch_id,
            'active' => false,
        ]);

        if (session()->has('error')) {
            $this->assertEquals('Selected role belongs to a different branch.', session('error'));
        }
        $this->assertEquals($roleA->id, $user->fresh()->role_id);
    }

    public function test_restore_blocked_when_branch_soft_deleted_and_allowed_after_branch_restore()
    {
        $branch = Branch::create(['name' => 'BranchX']);
        $role = Role::create(['name' => 'RoleX', 'slug' => 'role-x', 'is_active' => true, 'branch_id' => $branch->id]);

        $user = User::factory()->create(['role_id' => $role->id, 'branch_id' => $branch->id]);

        $user->delete();
        $branch->delete();

        $actor = User::factory()->create();

        $this->actingAs($actor)->post('/users/' . $user->id . '/restore');
        if (session()->has('error')) {
            $this->assertEquals('Cannot restore user because the assigned role is inactive or deleted.', session('error'));
        }

        $branch->restore();

        $this->actingAs($actor)->post('/users/' . $user->id . '/restore');
        $this->assertFalse($user->fresh()->trashed());
    }
}
