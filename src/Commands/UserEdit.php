<?php

namespace KajPe\UserCmd\Commands;

use KajPe\UserCmd\Exceptions\DuplicateUserException;
use KajPe\UserCmd\Exceptions\UnknownUserErrorException;
use KajPe\UserCmd\Helpers\PromptHelper;
use KajPe\UserCmd\Services\UserService;
use KajPe\UserCmd\Traits\UserHelper;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use function Laravel\Prompts\info;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\error;

class UserEdit extends Command
{
    use UserHelper;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:edit {id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Edit user';

    /**
     * Execute the console command.
     */
    public function handle(UserService $userService): int
    {
        $config = $this->getConfig();

        $opt = $this->getArgumentsAndOptions();

        if (is_null($opt->id)) {
            // If user didn't supply an id then show a list of users to select
            $users = $userService->GetAllUsersForSelect();
            $opt->id = PromptHelper::promptSelect(
                'Select user to edit (total users: ' . count($users) . ')',
                $users
            );
        }

        try {
            $user = $userService->GetUser($opt->id);
        } catch (ModelNotFoundException) {
            error('User (ID: ' . $opt->id . ') not found.');
            return 1;
        }

        $values = [];
        $spatie = null;
        foreach ($config->fields as $field=>$field_values) {
            $type = $field_values['type'] ?? '';
            $headerLabel = $this->buildLabel($field_values['label'] ?? null, $field, $config->id_key);

            if ($type == 'password') {
                if (confirm(label: 'Change ' . $headerLabel . '?', default: false)) {
                    $values[$field] = PromptHelper::promptPassword($headerLabel);
                }
                continue;
            }

            if ($type === 'text') {
                $values[$field] = PromptHelper::promptText($headerLabel, $user->{$field} ?? '');
                continue;
            }

            if ($type === 'select') {
                $values[$field] = PromptHelper::promptSelect(
                    $headerLabel,
                    $field_values['options'] ?? null,
                    $user->{$field} ?? $field_values['default'] ?? ''
                );
                dump($values[$field]);
                continue;
            }

            if ($type === 'spatie') {
                $spatie = PromptHelper::promptSpatie($headerLabel, $user->getPermissionNames()->toArray());
            }
        }

        // Update user
        try {
            $user = $userService->UpdateUser($user, $values, $spatie);
            info('User ' . $user->{$config->id_key} . ' (ID: ' . $user->id . ') has been updated.');
        } catch (DuplicateUserException) {
            error('Failed to update user. ' . $config->id_key . ' in use by other user.');
            return 4;
        } catch (UnknownUserErrorException $e) {
            error('Failed to update user with error: ' . $e->getMessage() . '.');
            return 5;
        }
        return 0;
    }
}
