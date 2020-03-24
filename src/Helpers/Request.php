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
    private $config;

    /**
     * Setters
     *
     * @param array $data
     * @param array $config
     */
    public function __construct(array $data, array $config)
    {
        $this->data = $data;
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
        $path = explode('.', $path);

        $data = $this->data;
        while ($item = array_shift($path)) {
            if (! isset($data[$item])) {
                return $default;
            }

            $data = $data[$item];
        }

        return $data;
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
        if (mb_strlen($path)) {
            $path = ".$path";
        }

        return $this->get($this->config['sort_key']."$path", $default);
    }

    /**
     * {@inheritDoc}
     * @see ArrayAccess::offsetSet()
     */
    public function offsetSet($offset, $value)
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
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * {@inheritDoc}
     * @see ArrayAccess::offsetUnset()
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * {@inheritDoc}
     * @see ArrayAccess::offsetGet()
     */
    public function offsetGet($offset)
    {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }
}
