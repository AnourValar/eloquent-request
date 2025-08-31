<?php

namespace AnourValar\EloquentRequest\Helpers;

class Request implements \ArrayAccess
{
    use ValidationTrait {
        getDisplayAttribute as getDisplayAttributeTrait;
    }

    /**
     * @var array
     */
    private $data;

    /**
     * @var array
     */
    private $profile;

    /**
     * @var array
     */
    private $config;

    /**
     * @var \Illuminate\Database\Eloquent\Builder
     */
    private \Illuminate\Database\Eloquent\Builder $query;

    /**
     * Setters
     *
     * @param array $data
     * @param array $profile
     * @param array $config
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return void
     */
    public function __construct(array $data, array $profile, array $config, \Illuminate\Database\Eloquent\Builder $query)
    {
        $this->data = $data;
        $this->profile = $profile;
        $this->config = $config;
        $this->query = $query;
    }

    /**
     * Cache key for the request
     *
     * @return string
     */
    public function cacheKey(): string
    {
        $keys = [__METHOD__, \EloquentSerialize::serialize($this->query), $this->profile, $this->normalizeKey($this->data), $this->config];
        return hash('sha256', json_encode($keys));
    }

    /**
     * Get display name of the attribute
     *
     * @param mixed $fields
     * @return string
     */
    public function getDisplayAttribute($fields): string
    {
        return $this->getDisplayAttributeTrait($this->query, $fields, $this->profile);
    }

    /**
     * Get element by path
     *
     * @param string|null $path
     * @param mixed $default
     * @return mixed
     */
    public function get(?string $path = null, $default = null)
    {
        $path = explode('.', (string) $path);
        $data = $this->data;

        $item = '';
        while (count($path) && $item .= array_shift($path)) {
            if (! isset($data[$item])) {
                $item .= '.';
                continue;
            }

            $data = $data[$item];
            $item = '';
        }

        if ($item === '') {
            return $data;
        }
        return $default;
    }

    /**
     * Get filter by path
     *
     * @param string|null $path
     * @param mixed $default
     * @return mixed
     */
    public function filter(?string $path = null, $default = null)
    {
        $path = (string) $path;
        if (mb_strlen($path)) {
            $path = ".$path";
        }

        return $this->get($this->config['filter_key']."$path", $default);
    }

    /**
     * Get relation by path
     *
     * @param string|null $path
     * @param mixed $default
     * @return mixed
     */
    public function relation(?string $path = null, $default = null)
    {
        $path = (string) $path;
        if (mb_strlen($path)) {
            $path = ".$path";
        }

        return $this->get($this->config['relation_key']."$path", $default);
    }

    /**
     * Get scope by path
     *
     * @param string|null $path
     * @param mixed $default
     * @return mixed
     */
    public function scope(?string $path = null, $default = null)
    {
        $path = (string) $path;
        if (mb_strlen($path)) {
            $path = ".$path";
        }

        return $this->get($this->config['scope_key']."$path", $default);
    }

    /**
     * Get sort by path
     *
     * @param string|null $path
     * @param mixed $default
     * @return mixed
     */
    public function sort(?string $path = null, $default = null)
    {
        $path = (string) $path;
        if (mb_strlen($path)) {
            $path = ".$path";
        }

        return $this->get($this->config['sort_key']."$path", $default);
    }

    /**
     * Get profile
     *
     * @return array
     */
    public function profile(): array
    {
        return $this->profile;
    }

    /**
     * Has any filters (applied)
     *
     * @param array $keys
     * @return bool
     */
    public function hasFilters(array $keys = ['filter_key', 'relation_key', 'scope_key']): bool
    {
        foreach ($keys as $key) {
            $value = ($this->data[$this->config[$key]] ?? null);
            $default = ($this->profile['default_request'][$this->config[$key]] ?? null);

            if ($value != $default || old($this->config[$key])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Has any sorts (applied)
     *
     * @return bool
     */
    public function hasSorts(): bool
    {
        return $this->hasFilters(['sort_key']);
    }

    /**
     * Has only specific params (applied)
     *
     * @param array $includeParams
     * @return bool
     */
    public function hasOnly(array $includeParams): bool
    {
        $default = $this->profile['default_request'];

        foreach ($this->get() as $key => $value) {
            if (array_key_exists($key, $default) && $default[$key] == $value) {
                continue;
            }

            if (in_array($key, $includeParams)) {
                continue;
            }

            return false;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     * @see ArrayAccess::offsetSet()
     */
    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    /**
     * {@inheritDoc}
     * @see ArrayAccess::offsetExists()
     */
    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }

    /**
     * {@inheritDoc}
     * @see ArrayAccess::offsetUnset()
     */
    public function offsetUnset($offset): void
    {
        unset($this->data[$offset]);
    }

    /**
     * {@inheritDoc}
     * @see ArrayAccess::offsetGet()
     */
    public function offsetGet($offset): mixed
    {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }

    /**
     * @param array $keys
     * @return array
     */
    private function normalizeKey(array $keys): array
    {
        foreach ($keys as &$item) {
            if (is_array($item)) {
                $item = $this->normalizeKey($item);
            }

            if (is_integer($item) || is_double($item)) {
                $item = (string) $item;
            }

            if ($item === true) {
                $item = '1';
            }

            if ($item === false) {
                $item = '0';
            }

            if (is_string($item)) {
                $item = trim($item);
            }
        }
        unset($item);

        return $keys;
    }
}
