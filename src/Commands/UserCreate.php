<?php

namespace KajPe\UserCmd\Commands;

use KajPe\UserCmd\Exceptions\DuplicateUserException;
use KajPe\UserCmd\Exceptions\UnknownUserErrorException;
use KajPe\UserCmd\Helpers\PromptHelper;
use KajPe\UserCmd\Services\UserService;
use KajPe\UserCmd\Traits\UserHelper;
use Illuminate\Console\Command;

use function Laravel\Prompts\info;
use function Laravel\Prompts\error;

class UserCreate extends Command
{
    use UserHelper;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create new user';

    /**
     * Execute the console command.
     */
    public function handle(UserService $userService): int
    {
        $config = $this->getConfig();

        $values = [];
        $spatie = null;
        foreach ($config->fields as $field=>$field_values) {
            $type = $field_values['type'] ?? '';
            $headerLabel = $this->buildLabel($field_values['label'] ?? null, $field, $config->id_key);

            if ($type == 'password') {
                $values[$field] = PromptHelper::promptPassword($headerLabel);
                continue;
            }

            if ($type === 'text') {
                $values[$field] = PromptHelper::promptText($headerLabel);
                continue;
            }

            if ($type === 'select') {
                $values[$field] = PromptHelper::promptSelect(
                    $headerLabel,
                    $field_values['options'] ?? null,
                    $field_values['default'] ?? ''
                );
                continue;
            }

            if ($type === 'spatie') {
                $spatie = PromptHelper::promptSpatie($headerLabel);
            }
        }

        // Create user
        try {
            $user = $userService->CreateUser($values, $spatie);
            info('User ' . $user->{$config->id_key} . ' has been created with ID: ' . $user->id . '.');
        } catch (DuplicateUserException) {
            error('Failed to create user. ' . $config->id_key . ' in use by other user.');
            return 2;
        } catch (UnknownUserErrorException $e) {
            error('Failed to create user with error: ' . $e->getMessage() . '.');
            return 3;
        }

        return 0;
    }
}
