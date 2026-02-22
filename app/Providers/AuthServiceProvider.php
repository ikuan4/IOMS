<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Branch;
use App\Models\ContractType;
use App\Models\Contract;
use App\Models\Role;
use App\Models\TicketType;
use App\Models\TicketModule;
use App\Models\Ticket;
use App\Models\User;
use App\Policies\BranchPolicy;
use App\Policies\ContractTypePolicy;
use App\Policies\ContractPolicy;
use App\Policies\RolePolicy;
use App\Policies\TicketTypePolicy;
use App\Policies\TicketModulePolicy;
use App\Policies\TicketPolicy;
use App\Policies\UserPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Role::class => RolePolicy::class,
        Branch::class => BranchPolicy::class,
        ContractType::class => ContractTypePolicy::class,
        Contract::class => ContractPolicy::class,
        TicketType::class => TicketTypePolicy::class,
        TicketModule::class => TicketModulePolicy::class,
        Ticket::class => TicketPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        // Keep Gate::before in AppServiceProvider if present for Developer bypass.
    }
}
