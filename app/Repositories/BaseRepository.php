<?php

declare(strict_types=1);

namespace App\Repositories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

abstract class BaseRepository
{
    protected array $_cache = [];

    protected Logger $logger;

    public function __construct(?Logger $logger = null)
    {
        if (! $logger) {
            $logger = new Logger('smart-dato-hub');
            $logger->pushHandler(new StreamHandler(storage_path('/logs/'.Carbon::now()->format('Y-m-d').'-smart-dato-hub.log'), Level::Debug));

            $this->logger = $logger;
        }
    }

    public function findModelWithRelations(string $modelClass, ?string $fieldName, mixed $fieldValue, array $associations = []): Builder
    {
        $result = $modelClass::with($associations);

        if ($fieldName && $fieldValue) {
            $result->where($fieldName, $fieldValue);
        }

        return $result;
    }

    public function findModelByField(string $modelClass, mixed $fieldValue, string $fieldName, array $associations = [], string $modelCacheName = ''): ?Model
    {
        if (! $fieldName) {
            return null;
        }

        $useCache = $this->useCache($modelCacheName);

        if ($useCache) {
            $cacheKey = $fieldName.'_'.$fieldValue;
            if ($associations) {
                $cacheKey = $fieldName.'_'.$fieldValue.'_'.md5(json_encode($associations));
            }
        }

        if ($useCache && $this->existsInCache($modelCacheName, $cacheKey) && $this->getFromCache($modelCacheName, $cacheKey)) {
            return $this->getFromCache($modelCacheName, $cacheKey);
        }

        if ($associations) {
            $pimModel = $this->findModelWithRelations($modelClass, $fieldName, $fieldValue, $associations)->first();
        } else {
            $pimModel = $modelClass::where($fieldName, $fieldValue)->first();
        }

        if ($pimModel && $useCache) {
            $this->saveInCache($modelCacheName, $cacheKey, $pimModel);
        }

        return $pimModel;
    }

    protected function getFromCache(string $cacheName, string $key): mixed
    {
        if (! $this->existsInCache($cacheName, $key)) {
            return null;
        }

        return $this->_cache[$cacheName][md5($key)];
    }

    protected function existsInCache(string $cacheName, string $key): bool
    {
        return isset($this->_cache[$cacheName][md5($key)]);
    }

    protected function saveInCache(string $cacheName, string $key, mixed $data): mixed
    {
        $this->_cache[$cacheName][md5($key)] = $data;

        return $this->getFromCache($cacheName, $key);
    }

    protected function getAndSaveInCache(string $cacheName, string $key, mixed $data): mixed
    {
        if (! $this->getFromCache($cacheName, $key)) {
            return $this->saveInCache($cacheName, $key, $data);
        }

        return $this->getFromCache($cacheName, $key);
    }

    private function useCache(string $modelCacheName): bool
    {
        return ! empty($modelCacheName);
    }
}
