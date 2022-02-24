<?php

namespace AnourValar\EloquentRequest\Helpers;

use Illuminate\Database\Eloquent\Builder;

class Request implements \ArrayAccess
{
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
     * Setters
     *
     * @param array $data
     * @param array $profile
     * @param array $config
     */
    public function __construct(array $data, array $profile, array $config)
    {
        $this->data = $data;
        $this->profile = $profile;
        $this->config = $config;
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
        if (mb_strlen($path)) {
            $path = ".$path";
        }

        return $this->get($this->config['filter_key']."$path", $default);
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
