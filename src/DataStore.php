<?php

namespace jonathanraftery\Bullhorn;

Interface DataStore
{
    public function store($key, $value);
    public function get($key);
}
