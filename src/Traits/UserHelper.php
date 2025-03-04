<?php

namespace KajPe\UserCmd\Traits;

use stdClass;

trait UserHelper
{
    /**
     * Build label for header
     *
     * @param string|null $label
     * @param string $field
     * @param string $key
     * @return string
     */
    private function buildLabel(string|null $label, string $field, string $key=''): string
    {
       return ($label ?? $field) . ($key === $field ? ' (*)' : '');
    }

    /**
     * Read config values
     *
     * @return stdClass
     */
    public function getConfig(): stdClass
    {
        $config = new stdClass();
        $config->id_key = config('cmd-user.id', 'email');
        $config->fields = config('cmd-user.fields');
        return $config;
    }

    /**
     * Get arguments and options from commandline
     *
     * @return stdClass
     */
    public function getArgumentsAndOptions(): stdClass
    {
        $opt = new stdClass();
        $opt->id = $this->argument('id');
        $opt->sep = ($this->hasoption('sep') ? $this->option('sep') : null) ?? "\n";
        $opt->all = $this->hasoption('all') ? $this->option('all') : false;
        $opt->json = $this->hasoption('json') ? $this->option('json') : false;
        $opt->raw = $this->hasoption('raw') ? $this->option('raw') : false;
        return $opt;
    }
}
