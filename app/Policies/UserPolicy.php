<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view-users');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $model): bool
    {
        return $user->can('view-users') && $user->id === $model->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create-users');
    }

    /**
     * Can the user edit this user (e.g. change their role)?
     * A super-admin cannot be edited here.
     */
    public function update(User $user, User $model): bool
    {
        return $user->can('edit-users') 
            && ! $model->hasRole('super-admin');
    }

     /**
     * Can the user delete this user?
     * Nobody can delete themselves, and a super-admin cannot be deleted here.
     */
    public function delete(User $user, User $model): bool
    {
        return $user->can('delete-users')
            && $user->isNot($model)
            && ! $model->hasRole('super-admin');
    }

    public function deleteAdmin(User $user, User $model): bool
    {
        return $user->can('delete-users')
            && $user->isNot($model)
            && ! $model->hasRole('admin');
    }
}
