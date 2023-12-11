<?php

declare(strict_types=1);

namespace MrHDOLEK\SimpleRedisModel\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

class RedisModelMakerCommand extends GeneratorCommand
{
    public $signature = "redis-model:model {name : The name of the redis model}";
    public $description = "Create a new Redis model class.";

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = "Model";

    /**
     * Get the stub file for the generator.
     */
    protected function getStub(): string
    {
        return __DIR__ . "/stubs/model.stub";
    }

    /**
     * Get the root namespace for the class.
     */
    protected function rootNamespace(): string
    {
        return "App\\RedisModels";
    }

    /**
     * Get the root namespace for the class.
     */
    protected function getGenerationPath(): string
    {
        return app_path("RedisModels");
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     */
    protected function getPath($name): string
    {
        $name = Str::replaceFirst($this->rootNamespace(), "", $name);

        return $this->getGenerationPath() . str_replace("\\", "/", $name) . ".php";
    }
}
