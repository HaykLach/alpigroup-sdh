<?php

namespace App\Components;

use App\Traits\ReturnType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use ReflectionException;

abstract class BaseManager
{
    use ReturnType;

    /** @var string[] */
    protected const AFTER_INSERT_RELATIONS = [HasMany::class, HasOne::class];

    /** @var string[] */
    protected const MANY_TO_MANY_RELATIONS = [BelongsToMany::class];

    /** @var string */
    protected const HANDLE_AFTER_INSERT_KEY = 'after_insert';

    /** @var string */
    protected const HANDLE_MANY_TO_MANY_KEY = 'belongs_to_many';

    /** @var string */
    protected const HANDLE_BEFORE_INSERT_KEY = 'before_insert';

    protected Logger $logger;

    public function __construct(?Logger $logger = null)
    {
        if (! $logger) {
            $logger = new Logger('smart-dato-hub');
            $logger->pushHandler(new StreamHandler(storage_path('/logs/'.Carbon::now()->format('Y-m-d').'-smart-dato-hub.log'), Level::Debug));
        }

        $this->logger = $logger;
    }

    protected function camelCase(string $key): string
    {
        $parts = explode('_', $key);

        foreach ($parts as $i => $part) {
            if ($i !== 0) {
                $parts[$i] = ucfirst($part);
            }
        }

        return str_replace(' ', '', implode(' ', $parts));
    }

    protected function snakeCase(string $key): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $key));
    }

    /**
     * @throws ReflectionException
     */
    protected function prepareModelRelations(string $modelClass, array $relations): array
    {
        $result = [];

        $model = new $modelClass;

        foreach ($relations as $key => $relation) {
            if (! method_exists($model, $key)) {
                continue;
            }

            $returnType = $this->getMethodReturnType($modelClass, $key);

            if ($returnType && in_array($returnType, self::AFTER_INSERT_RELATIONS)) {
                $result[self::HANDLE_AFTER_INSERT_KEY][$key] = $relation;
            } elseif ($returnType && in_array($returnType, self::MANY_TO_MANY_RELATIONS)) {
                $result[self::HANDLE_MANY_TO_MANY_KEY][$key] = $relation;
            } elseif ($returnType) {
                $handlerFunction = 'handle'.ucfirst($key);
                if (method_exists($this, $handlerFunction)) {
                    $handleResult = $this->$handlerFunction($relation);
                    $result[self::HANDLE_BEFORE_INSERT_KEY][$key] = $handleResult;
                }
            }
        }

        return $result;
    }

    protected function handleModelRelations(Model $model, array $afterInsertRelations, array $manyToManyRelations): void
    {
        foreach ($afterInsertRelations as $key => $relationData) {
            $handlerFunction = 'handle'.ucfirst($key);
            if (! method_exists($this, $handlerFunction)) {
                continue;
            }

            $this->$handlerFunction($relationData, $model->id);
        }

        foreach ($manyToManyRelations as $key => $relationData) {
            $handlerFunction = 'handle'.ucfirst($key);
            if (! method_exists($this, $handlerFunction)) {
                continue;
            }

            $handleResult = $this->$handlerFunction($relationData);

            if (method_exists($model, $key)) {
                $model->$key()->attach($handleResult);
            }
        }
    }
}
