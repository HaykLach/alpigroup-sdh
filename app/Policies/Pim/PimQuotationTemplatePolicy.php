<?php

namespace App\Policies\Pim;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PimQuotationTemplatePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->can('view_any_pim::pim::quotation::template');
    }

    public function view(User $user): bool
    {
        return $user->can('view_pim::pim::quotation::template');
    }

    public function create(User $user): bool
    {
        return $user->can('create_pim::pim::quotation::template');
    }

    public function update(User $user): bool
    {
        return $user->can('update_pim::pim::quotation::template');
    }

    public function delete(User $user): bool
    {
        return $user->can('delete_pim::pim::quotation::template');
    }

    public function restore(User $user): bool
    {
        return $user->can('restore_pim::pim::quotation::template');
    }

    public function forceDelete(User $user): bool
    {
        return $user->can('force_delete_pim::pim::quotation::template');
    }
}
