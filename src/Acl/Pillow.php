<?php
/*
This file is part of SeAT

Copyright (C) 2015, 2016  Leon Jacobs

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/

namespace Seat\Web\Acl;

use Seat\Web\Models\Acl\Affiliation as AffiliationModel;
use Seat\Web\Models\Acl\Permission as PermissionModel;
use Seat\Web\Models\Acl\Role as RoleModel;
use Seat\Web\Models\User as UserModel;

/**
 * Class Pillow
 * @package Seat\Web\Acl
 */
trait Pillow
{

    /**
     * Return everything related to the Role
     * with eager loading
     *
     * @param null $role_id
     *
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function getCompleteRole($role_id = null)
    {

        $roles = RoleModel::with(
            'permissions', 'users', 'affiliations');

        if (!is_null($role_id)) {

            $roles = $roles->where('id', $role_id)
                ->first();

            if (!$roles)
                abort(404);

            return $roles;
        }

        return $roles->get();
    }

    /**
     * Get a role
     *
     * @param $id
     *
     * @return mixed
     */
    public function getRole($id)
    {

        return RoleModel::findOrFail($id);
    }

    /**
     * Add a new role
     *
     * @param $title
     */
    public function addRole($title)
    {

        RoleModel::create([
            'title' => $title,
        ]);

        return;
    }

    /**
     * Remove a role
     *
     * @param $id
     *
     * @return int
     */
    public function removeRole($id)
    {

        return RoleModel::destroy($id);
    }

    /**
     * Remove a role by title
     *
     * @param $title
     */
    public function removeRoleByTitle($title)
    {

        $role = RoleModel::where('title', $title)->first();
        $this->removeRole($role->id);

        return;
    }

    /**
     * Give a Role a permission
     *
     * @param int    $role_id
     * @param string $permission_name
     * @param bool   $inverse
     */
    public function giveRolePermission(int $role_id, string $permission_name, bool $inverse)
    {

        $role = $this->getRole($role_id);

        $permission = PermissionModel::firstOrNew([
            'title' => $permission_name
        ]);

        // If the role does not already have the permission
        // add it. We will also apply the inverse rule as an
        // extra attrivute on save()
        if (!$role->permissions->contains($permission->id))
            $role->permissions()->save($permission, ['not' => $inverse]);

        return;
    }

    /**
     * Give a role many permissions
     *
     * @param       $role_id
     * @param array $permissions
     * @param bool  $inverse
     */
    public function giveRolePermissions($role_id, array $permissions, bool $inverse)
    {

        foreach ($permissions as $key => $permission_name)
            $this->giveRolePermission($role_id, $permission_name, $inverse);

        return;
    }

    /**
     * Remove a permission from a Role
     *
     * @param $permission_id
     * @param $role_id
     */
    public function removePermissionFromRole($permission_id, $role_id)
    {

        $role = $this->getRole($role_id);
        $role->permissions()->detach($permission_id);

        return;

    }

    /**
     * Give a user a Role
     *
     * @param $user_id
     * @param $role_id
     */
    public function giveUserRole($user_id, $role_id)
    {

        $user = UserModel::find($user_id);

        $role = RoleModel::firstOrNew([
            'id' => $role_id
        ]);

        // If the role does not already have the user
        // add it.
        if (!$role->users->contains($user->id))
            $role->users()->save($user);

        return;

    }

    /**
     * Give an array of usernames a role
     *
     * @param array $user_names
     * @param       $role_id
     */
    public function giveUsernamesRole(array $user_names, $role_id)
    {

        foreach ($user_names as $user_name) {

            $user = UserModel::where('name', $user_name)->first();
            $this->giveUserRole($user->id, $role_id);
        }

        return;

    }

    /**
     * Remove a user from a role
     *
     * @param $user_id
     * @param $role_id
     */
    public function removeUserFromRole($user_id, $role_id)
    {

        $role = $this->getRole($role_id);
        $role->users()->detach($user_id);

        return;
    }

    /**
     * @param int  $role_id
     * @param int  $corporation_id
     * @param bool $inverse
     */
    public function giveRoleCorporationAffiliation(int $role_id, int $corporation_id, bool $inverse)
    {

        $role = $this->getRole($role_id);

        $affiliation = AffiliationModel::firstOrNew([
            'affiliation' => $corporation_id,
            'type'        => 'corp'
        ]);

        if (!$role->affiliations->contains($affiliation))
            $role->affiliations()->save($affiliation, ['not' => $inverse]);

        return;

    }

    /**
     * @param int   $role_id
     * @param array $affiliations
     * @param bool  $inverse
     */
    public function giveRoleCorporationAffiliations(int $role_id, array $affiliations, bool $inverse)
    {

        foreach ($affiliations as $affiliation)
            $this->giveRoleCorporationAffiliation($role_id, $affiliation, $inverse);

        return;
    }

    /**
     * @param int  $role_id
     * @param int  $character_id
     * @param bool $inverse
     */
    public function giveRoleCharacterAffiliation(int $role_id, int $character_id, bool $inverse)
    {

        $role = $this->getRole($role_id);

        $affiliation = AffiliationModel::firstOrNew([
            'affiliation' => $character_id,
            'type'        => 'char'
        ]);

        if (!$role->affiliations->contains($affiliation))
            $role->affiliations()->save($affiliation, ['not' => $inverse]);
    }

    /**
     * @param int   $role_id
     * @param array $affiliations
     * @param bool  $inverse
     */
    public function giveRoleCharacterAffiliations(int $role_id, array $affiliations, bool $inverse)
    {

        foreach ($affiliations as $affiliation)
            $this->giveRoleCharacterAffiliation($role_id, $affiliation, $inverse);

        return;
    }

    /**
     * @param $role_id
     * @param $affiliation_id
     */
    public function removeAffiliationFromRole($role_id, $affiliation_id)
    {

        $role = $this->getRole($role_id);
        $role->affiliations()->detach($affiliation_id);

        return;
    }

}
