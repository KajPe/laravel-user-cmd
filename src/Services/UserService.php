<?php


namespace KajPe\UserCmd\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use KajPe\UserCmd\Exceptions\DuplicateUserException;
use KajPe\UserCmd\Exceptions\UnknownUserErrorException;
use Throwable;

class UserService {

    /**
     * Get single user based on $id
     *
     * @param int $id
     * @return User
     */
    public function GetUser(int $id): User
    {
        return User::findOrFail($id);
    }

    /**
     * Get all (or single) users as collection. Include permissions if requested
     *
     * @param array $columns
     * @param int|null $id
     * @param bool $use_spatie
     * @return Collection
     */
    public function GetAllUsers(array $columns, int|null $id, bool $use_spatie): Collection
    {
        // Get users
        return User::query()
            ->select($columns)
            ->when($id !== null, function($query) use ($id) {
                $query->where('id', '=', $id);
            })
            ->when($use_spatie, function($query) {
                // Add spatie permissions
                $query->with('permissions');
            })
            ->get();
    }

    /**
     * Get all users for select prompt
     *
     * @return array
     */
    public function GetAllUsersForSelect(): array
    {
        return User::get(['id', 'name', 'email'])
            ->mapWithKeys(function ($item) {
                return [$item->id => $item->id . ') ' . $item->name . ' (' . $item->email . ')'];
            })
            ->toArray();
    }

    /**
     * Create user
     *
     * @param array $values
     * @param array|null $spatie
     * @return User
     * @throws DuplicateUserException
     * @throws UnknownUserErrorException
     */
    public function CreateUser(array $values, array|null $spatie): User
    {
        try {
            $user = new User();
            $user->fill($values);
            $user->saveOrFail();

            if (!is_null($spatie)) {
                $user->syncPermissions($spatie);
            }

            return $user;
        } catch (Throwable $e) {
            if ($e->getCode() === '23000') {
                throw new DuplicateUserException();
            }
            throw new UnknownUserErrorException($e->getMessage());
        }
    }

    /**
     * Update user
     *
     * @param User $user
     * @param array $values
     * @param array|null $spatie
     * @return User
     * @throws DuplicateUserException
     * @throws UnknownUserErrorException
     */
    public function UpdateUser(User $user, array $values, array|null $spatie): User
    {
        try {
            $user->fill($values);
            $user->saveOrFail();

            if (!is_null($spatie)) {
                $user->syncPermissions($spatie);
            }

            return $user;
        } catch (Throwable $e) {
            if ($e->getCode() === '23000') {
                throw new DuplicateUserException();
            }
            throw new UnknownUserErrorException($e->getMessage());
        }
    }

     /**
     * Remove user
     *
     * @param int $id
     * @return User
     * @throws UnknownUserErrorException
     */
    public function RemoveUser(int $id): User
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();

            return $user;
        } catch (ModelNotFoundException) {
            throw new ModelNotFoundException();
        } catch (Throwable $e) {
            throw new UnknownUserErrorException($e->getMessage());
        }
    }

    /**
     * Convert $users collection to flat array. Reorder according to header.
     *
     * @param Collection $users
     * @param array $fields
     * @param bool $raw
     * @return array
     */
    public function convertAndSortArray(Collection $users, array $fields, bool $raw=false): array
    {
        return array_map(function($user) use ($fields, $raw) {
            // Reorder array based on header (fields order)
            $array = [];

            // Add fields from headers first
            foreach ($fields as $field=>$field_values) {
                $value = $user[$field] ?? '';
                $type = $field_values['type'] ?? '';

                if ((!$raw) && ($type === 'select')) {
                    $array[$field] = $field_values['options'][$value] ?? $value;
                    continue;
                }

                // Store value as such
                $array[$field] = $value;
            }
            return $array;
        }, $users->toArray());
    }

    /**
     * Filter out keys which have 'exclude-list' set to true
     * The 'exclude-list' can have two values and depends on $all which is used.
     *
     * @param array $fields
     * @param bool $all
     * @return array
     */
    public function filterFields(array $fields, bool $all=false): array
    {
        return array_filter($fields, function ($item) use ($all) {
            $value = $item['exclude-list'] ?? false;
            if (is_array($value)) {
                // Array: $value[0] is list, $value[1] is column-list
                $value = ($value[0] && !$all) || ($value[1] && $all);
            }
            return !$value;
        });
    }

    /**
     * Convert permissions array to string
     *
     * @param Collection $users
     * @param string $sep
     * @return void
     */
    public function permissionsToString(Collection $users, string $sep): void
    {
        foreach($users as $user) {
            $permissions_as_string = ($user->permissions->isNotEmpty() ?
                $user->permissions->pluck('name')->implode($sep) : ''
            );
            // Setting the model permissions above wouldn't work as permissions is a relation table.
            // So you have to first unset it, to get away with the relation and then set it to the string.
            unset($user->permissions);
            $user->permissions = $permissions_as_string;
        }
    }
}
