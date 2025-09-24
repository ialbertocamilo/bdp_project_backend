<?php

namespace App\Http\Traits;

use Illuminate\Support\Facades\Cache;

trait CacheableTrait
{
    protected function cacheKey($key)
    {
        return class_basename($this) . '_' . $key;
    }

    protected function remember($key, $ttl, $callback)
    {
        return Cache::remember($this->cacheKey($key), $ttl, $callback);
    }

    protected function forget($key)
    {
        return Cache::forget($this->cacheKey($key));
    }

    protected function rememberForever($key, $callback)
    {
        return Cache::rememberForever($this->cacheKey($key), $callback);
    }

    protected function cacheFlush($pattern = null)
    {
        if ($pattern) {
            $keys = Cache::getRedis()->keys($this->cacheKey($pattern));
            foreach ($keys as $key) {
                Cache::forget($key);
            }
        } else {
            Cache::flush();
        }
    }
}