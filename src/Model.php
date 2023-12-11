<?php

declare(strict_types=1);

namespace MrHDOLEK\SimpleRedisModel;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Redis\RedisManager;
use Stringable;

abstract class Model implements Stringable
{
    public const CREATED_AT = "createdAt";
    public const UPDATED_AT = "updatedAt";

    protected array $fields = [];
    protected bool $exists = false;
    protected string $prefix = "";
    protected string $keyPrefixDelimiter = ":";
    protected string $key = "id";
    protected bool $timestamps = true;
    protected array $attributes = [];
    protected array $hidden = [];
    protected array $visible = [];
    protected array $casts = [];
    protected string $dateFormat = "Y-m-d g:i:s";

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    /**
     * @throws Exception
     */
    public static function __callStatic(string $method, array $parameters): mixed
    {
        $instance = static::newInstance();

        return call_user_func_array([$instance, $method], $parameters);
    }

    public function __toString(): string
    {
        return $this->toJson();
    }

    /**
     * @throws Exception
     */
    public static function create(array $attributes): static
    {
        $model = static::newInstance($attributes);
        $model->save();

        return $model;
    }

    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }

        return $this;
    }

    /**
     * @throws Exception
     */
    public function save(): self
    {
        $client = $this->getClient();
        $this->checkKeyAttributeExistence();
        $this->checkModelPrefixExistence();

        try {
            if ($this->timestamps) {
                $this->updateTimestamps();
            }

            $this->exists = true;

            $attributesToSave = [];

            foreach ($this->attributes as $key => $value) {
                $attributesToSave[$key] = $this->castAttribute($key, $value);
            }

            $filteredAttributes = array_filter($attributesToSave, fn($key) => is_string($key), ARRAY_FILTER_USE_KEY);
            $client->hmset($this->getKeyPrefix() . $this->getKeyDelimiter() . $this->getAttribute($this->getKeyName()), $filteredAttributes);
        } catch (Exception $exception) {
            throw new Exception("Error saving the model: " . $exception->getMessage());
        }

        return $this;
    }

    /**
     * @throws Exception
     */
    public function delete(): ?bool
    {
        $this->checkKeyAttributeExistence();

        if ($this->exists) {
            $client = $this->getClient();
            $key = $this->getKeyPrefix() . $this->getKeyDelimiter() . $this->getAttribute($this->getKeyName());

            if ($client->del($key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @throws Exception
     */
    public static function destroy(int|array $ids): int
    {
        $instance = static::newInstance();
        $client = $instance->getClient();

        $ids = is_array($ids) ? $ids : func_get_args();
        $destroyedModelsCount = 0;

        foreach ($ids as $key => $value) {
            $key = $instance->getKeyPrefix() . $instance->getKeyDelimiter() . $value;

            if ($client->del($key)) {
                $destroyedModelsCount++;
            }
        }

        return $destroyedModelsCount;
    }

    /**
     * @throws Exception
     * @throws ModelNotFoundException
     */
    public static function find($id): static
    {
        $instance = static::newInstance();
        $client = $instance->getClient();

        try {
            $attributes = $client->hgetall($instance->getKeyPrefix() . $instance->getKeyDelimiter() . $id);

            if (empty($attributes)) {
                throw new ModelNotFoundException();
            }

            $model = static::newInstance($attributes);

            $model->exists = true;

            return $model;
        } catch (Exception $exception) {
            throw new Exception("Error fetching the model: " . $exception->getMessage());
        }
    }

    /**
     * @throws Exception
     */
    public static function all(): array
    {
        $instance = static::newInstance();
        $client = $instance->getClient();
        $models = [];

        try {
            $keys = $client->keys($instance->getKeyPrefix() . "*");

            foreach ($keys as $key => $value) {
                $attributes = $client->hgetall($value);
                $model = static::newInstance($attributes);
                $model->exists = true;

                $models[] = $model;
            }

            return $models;
        } catch (Exception $exception) {
            throw new Exception("Error fetching the models: " . $exception->getMessage());
        }
    }

    public function setCreatedAt(mixed $value): static
    {
        $this->{static::CREATED_AT} = $value;

        return $this;
    }

    public function freshTimestamp(): string
    {
        $actualDate = new Carbon();

        return $actualDate->format($this->dateFormat);
    }

    public function attributesToArray(): array
    {
        return $this->attributes;
    }

    public function filterAttributes(array $attributes): array
    {
        $filteredArray = $attributes;

        if (!empty($this->hidden)) {
            $filteredArray = array_diff_key($attributes, array_flip($this->hidden));
        }

        if (!empty($this->visible)) {
            $filteredArray = array_intersect_key($filteredArray, array_flip($this->visible));
        }

        return $filteredArray;
    }

    public function getKeyPrefix(): string
    {
        return $this->prefix;
    }

    public function getKeyDelimiter(): string
    {
        return $this->keyPrefixDelimiter;
    }

    public function getKeyName(): string
    {
        return $this->key;
    }

    public function setAttribute($key, $value): static
    {
        $this->attributes[$key] = $this->castAttribute($key, $value);

        return $this;
    }

    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public function getClient(): Client
    {
        return app(RedisManager::class)->client();
    }

    /**
     * @throws Exception
     */
    public function checkKeyAttributeExistence(): void
    {
        if (empty($this->attributes[$this->key])) {
            throw new Exception("Error: The model doesn't contain an id attribute");
        }
    }

    /**
     * @throws Exception
     */
    public function checkModelPrefixExistence(): void
    {
        if (empty($this->prefix)) {
            throw new Exception("Error: The model doesn't have a key prefix");
        }
    }

    public function exists(): bool
    {
        return $this->exists;
    }

    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options);
    }

    public function toArray(): array
    {
        return $this->attributesToArray();
    }

    protected function castAttribute($key, $value)
    {
        if (!array_key_exists($key, $this->casts) || $value === null) {
            return $value;
        }

        $type = $this->casts[$key];

        return match ($type) {
            "int", "integer" => (int)$value,
            "real", "float", "double" => (float)$value,
            "string" => (string)$value,
            "bool", "boolean" => (bool)$value,
            "object" => is_array($value) ? (object)$value : json_decode($value, false),
            "array", "json" => $this->castJson($value),
            "collection" => collect($this->castJson($value)),
            "date", "datetime" => Carbon::parse($value)->format($this->dateFormat),
            default => $value,
        };
    }

    protected function castJson($value)
    {
        return is_string($value) ? json_decode($value, true) : json_encode($value);
    }

    protected function updateTimestamps(): void
    {
        $time = $this->freshTimestamp();

        if (!$this->exists) {
            $this->setCreatedAt($time);
        }
    }

    /**
     * @throws Exception
     */
    protected static function newInstance(array $attributes = []): static
    {
        throw new Exception("The newInstance method must be overridden in the derived class.");
    }
}
