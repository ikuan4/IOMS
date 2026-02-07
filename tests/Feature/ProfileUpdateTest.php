<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_edit_renders_for_authenticated_user_with_role(): void
    {
        $role = Role::factory()->create();
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk();
    }

    public function test_profile_update_mobile_only_succeeds_even_if_permission_tables_missing(): void
    {
        $role = Role::factory()->create();
        $user = User::factory()->create(['role_id' => $role->id]);

        // Simulate the production failure mode: sidebar/policies may query permission tables,
        // but updating the profile itself should not require them.
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('permissions');
        Schema::enableForeignKeyConstraints();

        // Follow the redirect to ensure the profile page renders without 500,
        // even if permission tables are missing (it should fail closed).
        $this->actingAs($user)
            ->followingRedirects()
            ->put(route('profile.update'), [
                'name' => 'Updated Name',
                'mobile' => '1234567890',
            ])
            ->assertOk()
            ->assertSee('My Profile')
            ->assertSee('Profile updated successfully.');

        $user->refresh();
        $this->assertSame('Updated Name', $user->name);
        $this->assertSame('1234567890', $user->mobile);
    }

    public function test_permission_protected_route_denies_access_when_permission_tables_missing(): void
    {
        $role = Role::factory()->create();
        $user = User::factory()->create(['role_id' => $role->id]);

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('permissions');
        Schema::enableForeignKeyConstraints();

        // This hits UserController@index -> $this->authorize('viewAny', User::class)
        // and should be forbidden (403), not a 500.
        $this->actingAs($user)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_profile_update_password_only_succeeds_without_avatar(): void
    {
        $role = Role::factory()->create();
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => $user->name,
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'Profile updated successfully.');

        $user->refresh();
        $this->assertTrue(Hash::check('new-password-123', $user->password));
    }

    public function test_profile_update_with_avatar_does_not_500_when_cloudinary_not_configured(): void
    {
        $role = Role::factory()->create();
        $user = User::factory()->create(['role_id' => $role->id]);

        // Force Cloudinary to be "not configured" (this would previously abort(500)).
        config()->set('filesystems.disks.cloudinary', [
            'driver' => 'cloudinary',
            'url' => null,
            'cloud' => null,
            'key' => null,
            'secret' => null,
            'secure' => true,
        ]);

        $avatar = UploadedFile::fake()->create('avatar.jpg', 20, 'image/jpeg');

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => 'Updated Name',
                'mobile' => '1234567890',
                'avatar' => $avatar,
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'Profile updated successfully.')
            ->assertSessionHas('error', 'Profile photo upload failed (Cloudinary not configured or unreachable).');

        $user->refresh();
        $this->assertSame('Updated Name', $user->name);
        $this->assertSame('1234567890', $user->mobile);
    }

    public function test_profile_update_with_avatar_does_not_500_when_cloudinary_has_placeholder_api_key(): void
    {
        $role = Role::factory()->create();
        $user = User::factory()->create(['role_id' => $role->id]);

        // Matches the confirmed production failure mode: literal placeholder API key.
        config()->set('filesystems.disks.cloudinary', [
            'driver' => 'cloudinary',
            'url' => null,
            'cloud' => 'CLOUD_NAME',
            'key' => 'API_KEY',
            'secret' => 'API_SECRET',
            'secure' => true,
        ]);

        $avatar = UploadedFile::fake()->create('avatar.jpg', 20, 'image/jpeg');

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => 'Updated Name',
                'mobile' => '1234567890',
                'avatar' => $avatar,
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'Profile updated successfully.')
            ->assertSessionHas('error', 'Profile photo upload failed (Cloudinary not configured or unreachable).');

        $user->refresh();
        $this->assertSame('Updated Name', $user->name);
        $this->assertSame('1234567890', $user->mobile);
    }

    public function test_has_permission_fails_closed_when_permission_tables_missing(): void
    {
        $role = Role::factory()->create();
        $user = User::factory()->create(['role_id' => $role->id]);

        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('permissions');
        Schema::enableForeignKeyConstraints();

        // Before the fail-closed fix, this would throw a query exception.
        $this->assertFalse($user->hasPermission('users.view'));
    }
}
