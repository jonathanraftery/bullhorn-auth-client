<?php

namespace jonathanraftery\Bullhorn;

class MemoryDataStore implements DataStore
{
    private $data;

    public function store($key, $value)
    { $this->data[$key] = $value; }

    public function get($key)
    { return isset($this->data[$key]) ? $this->data[$key] : null; }
}
