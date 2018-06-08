<?php

namespace App\Console\Commands;

use RuntimeException;
use Illuminate\Console\Command;
use App\Support\PackageHandler;
use Symfony\Component\Console\Input\InputArgument;

class PackageHandlerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'package:handle';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run handler of package.';

    /**
     * Run the command.
     *
     * @return mixed
     */
    public function handle()
    {
        $name = $this->argument('name');
        if (! $name) {
            return $this->displayHandles();
        } elseif (! ($handler = $this->getHandles()[$name] ?? false)) {
            throw new RuntimeException('The handler not exist.');
        }

        $handle = $this->argument('handle');
        if (! $handle) {
            return $this->displayMethod($name, $this->resolveHandler($handler));
        }

        return $this->resolveHandler($handler)->handle($this, $handle);
    }

    /**
     * Get package handles.
     *
     * @return array
     */
    public function getHandles(): array
    {
        return PackageHandler::getHandles();
    }

    /**
     * display handle.
     *
     * @return mixed
     */
    protected function displayHandles()
    {
        foreach ($this->getHandles() as $name => $handler) {
            $this->warn($name);
            $this->displayMethod($name, $this->resolveHandler($handler));
        }
    }

    /**
     * Display handler method.
     *
     * @param string $name
     * @param PackageHandler $handler
     * @return mixed
     */
    protected function displayMethod(string $name, PackageHandler $handler)
    {
        $methods = $handler->methods();

        foreach ($methods as $method) {
            $this->info(sprintf('  - %s %s', $name, $method));
        }
    }

    /**
     * Resolve handler.
     *
     * @param string|PackageHandler $handler
     * @return PackageHandler
     */
    protected function resolveHandler($handler): PackageHandler
    {
        if ($handler instanceof PackageHandler) {
            return $handler;
        }

        return $this->getLaravel()->make($handler);
    }

    /**
     * get command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::OPTIONAL, 'The name of the command.'],
            ['handle', InputArgument::OPTIONAL, 'The name of the handler.'],
        ];
    }
}
