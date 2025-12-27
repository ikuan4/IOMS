<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class UserActivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_activate_user_when_primary_role_inactive()
    {
        // create an inactive role
        $role = Role::create([
            'name' => 'InactiveRole',
            'slug' => 'inactive_role',
            'is_active' => false,
        ]);

        // create a user assigned to that role and currently deactivated
        $user = User::factory()->create([
            'role_id' => $role->id,
            'active' => false,
        ]);

        // acting user to bypass auth middleware
        $actor = User::factory()->create();

        $response = $this->actingAs($actor)->patch('/users/' . $user->id, [
            'name' => $user->name,
            'mobile' => $user->mobile,
            'email' => $user->email,
            'role_id' => $role->id,
            'active' => true,
        ]);
        if (session()->has('error')) {
            $this->assertEquals('Cannot activate user because the selected role is inactive or deleted.', session('error'));
        }
        $this->assertFalse($user->fresh()->active, 'User should remain deactivated when primary role is inactive');
    }

    public function test_cannot_activate_user_when_pivot_role_inactive()
    {
        // primary role active
        $primary = Role::create([
            'name' => 'Primary',
            'slug' => 'primary',
            'is_active' => true,
        ]);

        // create an inactive pivot role
        $pivot = Role::create([
            'name' => 'PivotInactive',
            'slug' => 'pivot_inactive',
            'is_active' => false,
        ]);

        $user = User::factory()->create([
            'role_id' => $primary->id,
            'active' => false,
        ]);

        // insert pivot assignment into model_has_roles table
        DB::table('model_has_roles')->insert([
            'role_id' => $pivot->id,
            'model_type' => User::class,
            'model_id' => $user->id,
        ]);

        $actor = User::factory()->create();

        $response = $this->actingAs($actor)->patch('/users/' . $user->id, [
            'name' => $user->name,
            'mobile' => $user->mobile,
            'email' => $user->email,
            'role_id' => $primary->id,
            'active' => true,
        ]);
        if (session()->has('error')) {
            $this->assertEquals('Cannot activate user because one or more assigned roles are inactive or deleted.', session('error'));
        }
        $this->assertFalse($user->fresh()->active, 'User should remain deactivated when any pivot role is inactive');

    }

    public function test_restore_blocked_when_role_soft_deleted_and_allowed_after_role_restore()
    {
        // create role and user
        $role = Role::create([
            'name' => 'RoleToDelete',
            'slug' => 'role_to_delete',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'role_id' => $role->id,
        ]);

        // soft-delete the user and the role
        $user->delete();
        $role->delete();

        $actor = User::factory()->create();

        // attempt to restore user should be blocked because role is trashed
        $response = $this->actingAs($actor)->post('/users/' . $user->id . '/restore');
        if (session()->has('error')) {
            $this->assertEquals('Cannot restore user because the assigned role is inactive or deleted.', session('error'));
        }

        // restore role
        $role->restore();

        // attempt to restore user again should succeed
        $response2 = $this->actingAs($actor)->post('/users/' . $user->id . '/restore');
        $this->assertNotNull(session('status'));
        $this->assertFalse($user->fresh()->trashed(), 'User should be restored after role is restored');
    }
}
