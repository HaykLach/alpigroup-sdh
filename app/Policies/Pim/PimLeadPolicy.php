<?php

namespace App\Policies\Pim;

use App\Models\Pim\PimLead;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PimLeadPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_pim::pim::lead');
    }

    public function view(User $user, PimLead $pimLead): bool
    {
        return $user->can('view_pim::pim::lead');
    }

    public function create(User $user): bool
    {
        return $user->can('create_pim::pim::lead');
    }

    public function update(User $user, PimLead $pimLead): bool
    {
        return $user->can('update_pim::pim::lead');
    }

    public function delete(User $user, PimLead $pimLead): bool
    {
        return $user->can('delete_pim::pim::lead');
    }

    public function restore(User $user, PimLead $pimLead): bool
    {
        return $user->can('restore_pim::pim::lead');
    }

    public function forceDelete(User $user, PimLead $pimLead): bool
    {
        return $user->can('force_delete_pim::pim::lead');
    }
}
