<?php

namespace jonathanraftery\Bullhorn;

class JsonDataStore implements DataStore
{
    const STORE_FILE_NAME = './data-store.json';

    public function store($key, $value)
    {
        echo "\nstoring $key -> $value\n";
        $data = $this->readDataFile();
        $data->tokens->$key = $value;
        $this->saveData($data);
    }

    public function get($key)
    {
        $data = $this->readDataFile();
        return $data->tokens->$key;
    }

    private function readDataFile()
    {
        if (file_exists(self::STORE_FILE_NAME)) {
            $storeFile = fopen(self::STORE_FILE_NAME, 'r');
            $data = json_decode(file_get_contents(self::STORE_FILE_NAME));
            fclose($storeFile);
            return $data;
        }
        else
            return json_decode('{"tokens":{}}');
    }

    private function saveData($newData)
    {
        $storeFile = fopen(self::STORE_FILE_NAME, 'w');
        fwrite($storeFile, json_encode($newData));
        fclose($storeFile);
    }
}
