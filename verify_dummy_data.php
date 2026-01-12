<?php

use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Models\ContractType;
use App\Models\Contract;
use App\Models\NotificationRecipient;

echo "\n========================================\n";
echo "IOMS Dummy Data Verification Report\n";
echo "========================================\n\n";

echo "📊 Overall Statistics:\n";
echo "   - Branches: " . Branch::count() . "\n";
echo "   - Roles: " . Role::count() . "\n";
echo "   - Users: " . User::count() . "\n";
echo "   - Contract Types: " . ContractType::count() . "\n";
echo "   - Contracts: " . Contract::count() . "\n";
echo "   - Notification Recipients: " . NotificationRecipient::count() . "\n\n";

echo "🏢 Branches Details:\n";
$branches = Branch::with(['roles', 'users'])->get();
foreach ($branches as $branch) {
    $userCount = User::where('branch_id', $branch->id)->count();
    $contractCount = Contract::where('branch_id', $branch->id)->count();
    echo "   • {$branch->name}\n";
    echo "     - Roles: {$branch->roles->count()}\n";
    echo "     - Users: {$userCount}\n";
    echo "     - Contracts: {$contractCount}\n\n";
}

echo "📋 Contract Types with Contracts:\n";
$contractTypes = ContractType::withCount('contracts')->get();
foreach ($contractTypes as $type) {
    echo "   • {$type->name} ({$type->code})\n";
    echo "     - Contracts: {$type->contracts_count}\n";
}

echo "\n📝 Sample Contract with Recipients:\n";
$sampleContract = Contract::with(['notificationRecipients', 'contractType'])->first();
if ($sampleContract) {
    echo "   • Contract: {$sampleContract->contract_number}\n";
    echo "     - Type: {$sampleContract->contractType->name}\n";
    echo "     - With: {$sampleContract->contract_with}\n";
    echo "     - Recipients: {$sampleContract->notificationRecipients->count()}\n";
    foreach ($sampleContract->notificationRecipients as $recipient) {
        echo "       - {$recipient->name} ({$recipient->designation})\n";
    }
}

echo "\n✅ Verification Complete!\n\n";
