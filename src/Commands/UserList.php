<?php

namespace KajPe\UserCmd\Commands;

use KajPe\UserCmd\Services\UserService;
use KajPe\UserCmd\Traits\UserHelper;
use Exception;
use Illuminate\Console\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\table;

class UserList extends Command
{
    use UserHelper;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:list {id?} {--sep=} {--all} {--json} {--raw}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List users';

    /**
     * Execute the console command.
     * @throws Exception
     */
    public function handle(UserService $userService): int
    {
        $config = $this->getConfig();
        $opt = $this->getArgumentsAndOptions();

        // Filter out those keys we don't want to show in the list
        $fields = $userService->filterFields($config->fields, $opt->all);

        $headers = [];
        $columns = [];
        $spatie = false;
        foreach ($fields as $field=>$field_values) {
            $type = $field_values['type'] ?? '';

            $headerLabel = $this->buildLabel($field_values['label'] ?? null, $field, $config->id_key);

            if ($type == 'spatie') {
                // Don't add spatie column into columns, retrieve separately
                if (class_exists('Spatie\Permission\Models\Permission')) {
                    $headers[] = $headerLabel;
                    $spatie = true;
                }
                continue;
            }

            $headers[] = $headerLabel;
            $columns[] = $field;
        }

        // Get users
        $users = $userService->GetAllUsers($columns, $opt->id, $spatie);

        if ($spatie) {
            // Spatie permissions are in related table, so calculate a string of permissions
            $userService->permissionsToString($users, $opt->sep);
        }

        if ($users->count() == 0) {
            error('User (ID: ' . $opt->id . ') not found.');
            return 1;
        }

        $rows = $userService->convertAndSortArray($users, $fields, $opt->raw);

        if ($opt->json) {
            $json=[];
            foreach($rows as $row) {
                $result=[];
                foreach ($fields as $field => $field_values) {
                    $result[$field] = $row[$field] ?? '';
                }
                $json[] = $result;
            }
            echo json_encode($json) . "\n";
            return 0;
        }

        if ($opt->id) {
            // Single user, show as columns
            $result=[];
            foreach($fields as $field => $field_values) {
                $result[] = [
                    $this->buildLabel($field_values['label'] ?? null, $field, $config->id_key),
                    $rows[0][$field] ?? '',
                ];
            }
            table(
                headers: [ 'Key', 'Value' ],
                rows: $result,
            );
            return 0;
        }

        // Show list of users
        table(
            headers: $headers,
            rows: $rows
        );

        return 0;
    }
}
