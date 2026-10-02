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
 * Permissions differ per method. `all()` and `find()` are open to agent,
 * admin and customer tokens; a customer however only sees `id`, `active`,
 * `permission_ids` and `group_ids`, with `name` replaced by the placeholder
 * `"Role_<id>"`. `search()`, `searchList()`, `totalCount()`, `create()` and
 * `patch()` require `admin.role` and otherwise raise
 * {@see \ZammadAPIClient\Exceptions\ForbiddenException}.
 *
 * Zammad exposes no DELETE route for roles, so this repository does not
 * implement {@see \ZammadAPIClient\Core\Contracts\DeletableInterface}:
 * `delete()` throws a `BadMethodCallException`. Deactivate a role by patching
 * `active` to `false` instead.
 *
 * @extends AbstractRepository<RoleDTO>
 */
final class RoleRepository extends AbstractRepository
{
}
