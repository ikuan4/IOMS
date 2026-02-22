<?php

/**
 * Phase 5 Validation: Controller Refactor
 * 
 * This script validates that controllers correctly use the new authentication system
 * with global roles, branch roles, and active branch context.
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\Role;
use App\Models\Branch;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

echo "=======================================================\n";
echo "PHASE 5 VALIDATION: Controller Refactor\n";
echo "=======================================================\n\n";

$errors = [];
$passes = 0;
$tests = 0;

function test($description, $condition, &$passes, &$tests, &$errors) {
    $tests++;
    if ($condition) {
        $passes++;
        echo "✓ {$description}\n";
    } else {
        $errors[] = $description;
        echo "✗ {$description}\n";
    }
}

// ============================================
// Test 1: User Model Relationships
// ============================================
echo "\n--- Test 1: User Model Relationships ---\n";

$globalUser = User::whereNotNull('global_role_id')->first();
$branchUser = User::whereNull('global_role_id')->whereHas('branches')->first();

if ($globalUser) {
    test(
        "Global user has globalRole relationship",
        $globalUser->globalRole instanceof Role,
        $passes,
        $tests,
        $errors
    );

    test(
        "Global user's globalRole is marked as global",
        $globalUser->globalRole && $globalUser->globalRole->is_global,
        $passes,
        $tests,
        $errors
    );
}

if ($branchUser) {
    $branches = $branchUser->branches;
    test(
        "Branch user has branches relationship loaded",
        $branches->count() > 0,
        $passes,
        $tests,
        $errors
    );

    $branchRoles = $branchUser->branchRoles;
    test(
        "Branch user has branchRoles relationship loaded",
        $branchRoles->count() > 0,
        $passes,
        $tests,
        $errors
    );

    foreach ($branchRoles as $role) {
        test(
            "Branch role is NOT marked as global",
            !$role->is_global,
            $passes,
            $tests,
            $errors
        );
        break; // Just test first one
    }
}

// ==================================================================
// Test 2: Pivot Table Data Integrity
// ============================================
echo "\n--- Test 2: Pivot Table Data Integrity ---\n";

if ($branchUser) {
    // Check that branch_user pivots exist
    $branchUserCount = DB::table('branch_user')
        ->where('user_id', $branchUser->id)
        ->count();

    test(
        "Branch user has entries in branch_user pivot table",
        $branchUserCount > 0,
        $passes,
        $tests,
        $errors
    );

    // Check that branch_user_role pivots exist
    $branchUserRoleCount = DB::table('branch_user_role')
        ->where('user_id', $branchUser->id)
        ->count();

    test(
        "Branch user has entries in branch_user_role pivot table",
        $branchUserRoleCount > 0,
        $passes,
        $tests,
        $errors
    );

    // Check consistency: branch_user count should match branch_user_role count
    test(
        "Branch assignments are consistent (branch_user count = branch_user_role count)",
        $branchUserCount == $branchUserRoleCount,
        $passes,
        $tests,
        $errors
    );

    // Check that the pivot data matches the relationships
    $pivotBranchIds = DB::table('branch_user')
        ->where('user_id', $branchUser->id)
        ->pluck('branch_id')
        ->sort()
        ->values()
        ->toArray();

    $relationBranchIds = $branchUser->branches->pluck('id')->sort()->values()->toArray();

    test(
        "Pivot table data matches relationship data",
        $pivotBranchIds == $relationBranchIds,
        $passes,
        $tests,
        $errors
    );
}

// ============================================
// Test 3: Active Branch Session
// ============================================
echo "\n--- Test 3: Active Branch Session ---\n";

if ($branchUser) {
    // Set active branch
    $firstBranch = $branchUser->branches->first();
    Session::put('active_branch_id', $firstBranch->id);

    test(
        "Active branch session can be set",
        session('active_branch_id') == $firstBranch->id,
        $passes,
        $tests,
        $errors
    );

    // Test effectiveRole() method in active branch context
    $effectiveRole = $branchUser->effectiveRole();
    test(
        "Branch user has effective role in active branch",
        $effectiveRole instanceof Role,
        $passes,
        $tests,
        $errors
    );

    if ($effectiveRole) {
        $roleForBranch = $branchUser->roleForBranch($firstBranch->id);
        test(
            "roleForBranch() returns same role as effectiveRole() when in active branch",
            $roleForBranch && $effectiveRole && $roleForBranch->id == $effectiveRole->id,
            $passes,
            $tests,
            $errors
        );
    }
}

// ============================================
// Test 4: Global vs Branch User Distinction
// ============================================
echo "\n--- Test 4: Global vs Branch User Distinction ---\n";

$globalUserCount = User::whereNotNull('global_role_id')->count();
$branchUserCount = User::whereNull('global_role_id')->count();

test(
    "System has users with global roles",
    $globalUserCount > 0,
    $passes,
    $tests,
    $errors
);

test(
    "System has users with branch roles",
    $branchUserCount > 0,
    $passes,
    $tests,
    $errors
);

test(
    "No user has both global_role_id AND branch assignments",
    User::whereNotNull('global_role_id')
        ->whereHas('branches')
        ->count() == 0,
    $passes,
    $tests,
    $errors
);

// ============================================
// Test 5: Role Data Consistency
// ============================================
echo "\n--- Test 5: Role Data Consistency ---\n";

$globalRolesCount = Role::where('is_global', true)->count();
$branchRolesCount = Role::where('is_global', false)->count();

test(
    "System has global roles defined",
    $globalRolesCount > 0,
    $passes,
    $tests,
    $errors
);

test(
    "System has branch roles defined",
    $branchRolesCount > 0,
    $passes,
    $tests,
    $errors
);

// Check that global roles are not assigned to branches in pivot tables
$globalRoleIds = Role::where('is_global', true)->pluck('id');
$invalidPivots = DB::table('branch_user_role')
    ->whereIn('role_id', $globalRoleIds)
    ->count();

test(
    "No global roles are assigned as branch roles in pivot table",
    $invalidPivots == 0,
    $passes,
    $tests,
    $errors
);

// Check that users with global_role_id point to actual global roles
$usersWithInvalidGlobalRoles = User::whereNotNull('global_role_id')
    ->whereHas('globalRole', function($q) {
        $q->where('is_global', false);
    })
    ->count();

test(
    "All users with global_role_id point to actual global roles",
    $usersWithInvalidGlobalRoles == 0,
    $passes,
    $tests,
    $errors
);

// ============================================
// Summary
// ============================================
echo "\n=======================================================\n";
echo "VALIDATION SUMMARY\n";
echo "=======================================================\n";
echo "Tests Passed: {$passes}/{$tests}\n";

if (count($errors) > 0) {
    echo "\n❌ FAILED TESTS:\n";
    foreach ($errors as $error) {
        echo "  - {$error}\n";
    }
    echo "\nPhase 5 validation: FAILED ❌\n";
    exit(1);
} else {
    echo "\n✓ All tests passed! Phase 5 validation: SUCCESS ✓\n";
    echo "\nNote: This validates model relationships and data structure.\n";
    echo "Controller logic should be tested via feature tests or manual testing.\n";
    exit(0);
}
