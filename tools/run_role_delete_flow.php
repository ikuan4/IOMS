<?php
// Simulate role deletion flow: create role, assign active users, check mapped count, confirm deletion (soft-delete users), verify results.
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Role;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\RoleController;

echo "Starting role delete flow test...\n";
try {
    $admin = User::first();
    if (! $admin) { echo "No user found to act as actor.\n"; exit(1); }
    Auth::login($admin);

    $ts = time();
    $role = Role::create([ 'name' => "Test Role {$ts}", 'slug' => "test-role-{$ts}", 'guard_name' => 'web', 'is_active' => true ]);
    echo "Created role id={$role->id}\n";

    // Create two active users assigned to this role via role_id
    $u1 = User::create([
        'name' => "RoleUser1 {$ts}",
        'mobile' => '999' . substr((string)$ts, -7),
        'email' => "roleuser1_{$ts}@example.test",
        'password' => bcrypt('password'),
        'active' => true,
        'role_id' => $role->id,
        'created_by' => $admin->id,
    ]);
    $u2 = User::create([
        'name' => "RoleUser2 {$ts}",
        'mobile' => '998' . substr((string)$ts, -7),
        'email' => "roleuser2_{$ts}@example.test",
        'password' => bcrypt('password'),
        'active' => true,
        'role_id' => $role->id,
        'created_by' => $admin->id,
    ]);
    echo "Created users: {$u1->id}, {$u2->id}\n";

    $controller = new RoleController();

    // Check mapped active users
    $resp = $controller->mappedActiveUsers($role);
    $content = $resp->getContent();
    $data = json_decode($content, true);
    $count = $data['count'] ?? 0;
    echo "Mapped active users count: {$count}\n";

    // Now simulate confirm deletion: create Request with soft_delete_mapped_users=1
    $req = Request::create('/', 'DELETE', ['soft_delete_mapped_users' => 1]);
    $delResp = $controller->destroy($req, $role);
    echo "Destroy response redirected.\n";

    // Refresh users and role
    $u1 = User::withTrashed()->find($u1->id);
    $u2 = User::withTrashed()->find($u2->id);
    $role = Role::withTrashed()->find($role->id);

    echo "User 1 trashed: " . ($u1->trashed() ? 'yes' : 'no') . "\n";
    echo "User 2 trashed: " . ($u2->trashed() ? 'yes' : 'no') . "\n";
    echo "Role trashed: " . ($role->trashed() ? 'yes' : 'no') . "\n";

    // cleanup: force delete created records
    if ($u1) $u1->forceDelete();
    if ($u2) $u2->forceDelete();
    if ($role) $role->forceDelete();

    echo "Cleanup done. Test completed.\n";
    exit(0);
} catch (\Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
