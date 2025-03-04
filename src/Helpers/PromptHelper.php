<?php

namespace KajPe\UserCmd\Helpers;


use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;


class PromptHelper {

    /**
     * Show a text prompt
     * @param string $label
     * @param string $default
     * @return string
     */
    public static function promptText(string $label, string $default=''): string
    {
        return text(
            label: $label,
            default: $default
        );
    }

    /**
     * Show a select prompt
     *
     * @param string $label
     * @param array $options
     * @param int|string $default
     * @return int|string
     */
    public static function promptSelect(string $label, array $options, int|string $default=''): int|string
    {
        $output = select(
            label: $label,
            options: array_values($options),
            default: $options[$default] ?? ''
        );
        return array_search($output, $options);
    }

    /**
     * Show password prompt
     *
     * @param string $label
     * @return string
     */
    public static function promptPassword(string $label): string
    {
        return Hash::make(
            password(
                label: $label
            )
        );
    }

    /**
     * Show permissions as multiselect
     *
     * @param string $label
     * @param array $default
     * @return array|null
     */
    public static function promptSpatie(string $label, array $default=[]): array|null
    {
        if(!class_exists('Spatie\Permission\Models\Permission')) {
            return null;
        }
        return multiselect(
            label: $label,
            options: Permission::all()->mapWithKeys(function ($item) {
                return [$item->name => $item->id . ') ' . $item->name];
            })->toArray(),
            default: $default
        );
    }
}
