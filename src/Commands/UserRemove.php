<?php

namespace KajPe\UserCmd\Commands;

use KajPe\UserCmd\Helpers\PromptHelper;
use KajPe\UserCmd\Services\UserService;
use KajPe\UserCmd\Traits\UserHelper;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Throwable;

use function Laravel\Prompts\info;
use function Laravel\Prompts\error;

class UserRemove extends Command
{
    use UserHelper;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:remove {id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove user';

    /**
     * Execute the console command.
     */
    public function handle(UserService $userService): int
    {
        $opt = $this->getArgumentsAndOptions();

        if ($opt->id === null) {
            $users = $userService->GetAllUsersForSelect();
            $opt->id = PromptHelper::promptSelect(
                'Select user to remove (total users: ' . count($users) . ')',
                $users
            );
        }

        // Remove user
        try {
            $user = $userService->RemoveUser($opt->id);
            info('User ' . $user->name . ' (ID: ' . $opt->id . ') has been removed.');
        } catch (ModelNotFoundException) {
            error('User (ID: ' . $opt->id . ') not found.');
            return 1;
        } catch (Throwable $e) {
            error('Failed to remove user with error: ' . $e->getMessage() . '.');
            return 6;
        }

        return 0;
    }
}
