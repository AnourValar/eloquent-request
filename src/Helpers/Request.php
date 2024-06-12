<?php

namespace AnourValar\EloquentRequest\Helpers;

use Illuminate\Database\Eloquent\Builder;

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
     * @param string $path
     * @param mixed $default
     * @return mixed
     */
    public function get(string $path = null, $default = null)
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
     * @param string $path
     * @param mixed $default
     * @return mixed
     */
    public function filter(string $path = null, $default = null)
    {
        if (mb_strlen((string) $path)) {
            $path = ".$path";
        }

        return $this->get($this->config['filter_key']."$path", $default);
    }

    /**
     * Get relation by path
     *
     * @param string $path
     * @param mixed $default
     * @return mixed
     */
    public function relation(string $path = null, $default = null)
    {
        if (mb_strlen($path)) {
            $path = ".$path";
        }

        return $this->get($this->config['relation_key']."$path", $default);
    }

    /**
     * Get scope by path
     *
     * @param string $path
     * @param mixed $default
     * @return mixed
     */
    public function scope(string $path = null, $default = null)
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
     * @param string $path
     * @param mixed $default
     * @return mixed
     */
    public function sort(string $path = null, $default = null)
    {
        if (mb_strlen((string) $path)) {
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
     * @return bool
     */
    public function hasFilters(): bool
    {
        $filters = ($this->data[$this->config['filter_key']] ?? null);
        $default = ($this->profile['default_request'][$this->config['filter_key']] ?? null);
        return $filters != $default || old($this->config['filter_key']);
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
}
