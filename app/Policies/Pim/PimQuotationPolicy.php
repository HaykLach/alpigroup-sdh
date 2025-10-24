<?php

namespace App\Policies\Pim;

use App\Models\Pim\PimQuotation;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PimQuotationPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_pim::pim::quotation');
    }

    public function view(User $user, PimQuotation $pimQuotation): bool
    {
        return $user->can('view_pim::pim::quotation');
    }

    public function create(User $user): bool
    {
        return $user->can('create_pim::pim::quotation');
    }

    public function update(User $user, PimQuotation $pimQuotation): bool
    {
        return $user->can('update_pim::pim::quotation');
    }

    public function delete(User $user, PimQuotation $pimQuotation): bool
    {
        return $user->can('delete_pim::pim::quotation');
    }

    public function restore(User $user, PimQuotation $pimQuotation): bool
    {
        return $user->can('restore_pim::pim::quotation');
    }

    public function forceDelete(User $user, PimQuotation $pimQuotation): bool
    {
        return $user->can('force_delete_pim::pim::quotation');
    }
}
