<?php

declare(strict_types=1);

namespace ZammadAPIClient\Endpoints\Roles;

use ZammadAPIClient\Core\Repository\AbstractRepository;

/**
 * Repository for the `/api/v1/roles` endpoint.
 *
 * Roles bundle permissions in Zammad. Every user carries one or more roles via
 * the `role_ids` field on {@see \ZammadAPIClient\Endpoints\Users\UserDTO}; the
 * default installation ships with "Admin", "Agent" and "Customer".
 *
 * The typical use case is resolving a role name to its numeric ID before
 * creating or updating a user:
 *
 *   $roles = [];
 *   foreach ($client->role()->all() as $role) {
 *       $roles[$role->name] = $role->id;
 *   }
 *
 *   $client->user()->create(new UserDTO(
 *       email: 'agent@example.com',
 *       role_ids: [$roles['Agent']],
 *   ));
 *
 * Listing and reading roles requires admin permissions (`admin.role` or
 * `admin.user`); a token limited to agent permissions receives a 403 and the
 * client raises {@see \ZammadAPIClient\Exceptions\ForbiddenException}.
 *
 * Roles cannot be removed through the API, so this repository does not
 * implement {@see \ZammadAPIClient\Core\Contracts\DeletableInterface}:
 * `delete()` throws a `BadMethodCallException`. Deactivate a role by patching
 * `active` to `false` instead.
 *
 * @extends AbstractRepository<RoleDTO>
 */
final class RoleRepository extends AbstractRepository
{
}
