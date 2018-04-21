<?php

namespace jonathanraftery\Bullhorn;

class DataStore
{
    private $tokens;

    public function store($key, $value)
    {
        $this->tokens[$key] = $value;
    }

    public function get($key)
    {
        return $this->tokens[$key];
    }
}
