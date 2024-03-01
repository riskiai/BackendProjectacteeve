<?php

namespace App\Console\Commands;

use App\Models\Role;
use Illuminate\Console\Command;

class AddRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:add-role {name : name role}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $name = ucwords($name);

        $roles = Role::get()->pluck('name')->toArray();

        if (!in_array($name, $roles)) {
            Role::create([
                'name' => $name
            ]);
            $this->info("$name has been created.");
        } else {
            $this->error("$name already exists in database.");
        }
    }
}
