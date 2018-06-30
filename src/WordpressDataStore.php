<?php
	
namespace jonathanraftery\Bullhorn;

class WordpressDataStore implements DataStore
{	
    public function store($key, $value)
    {
        $data = $this->readDataFile();
        $data->tokens->$key = $value;
        $this->saveData($data);
    }

    public function get($key)
    {
        $data = $this->readDataFile();
        if (isset($data->tokens->$key))
            return $data->tokens->$key;
        else
            return null;
    }

    private function readDataFile()
    {
	    $data = get_transient("bullhorn-datastore");
	    
        if ($data)
            return json_decode($data);
        else
            return json_decode('{"tokens":{}}');
    }

    private function saveData($newData)
    {
        set_transient("bullhorn-datastore", json_encode($newData), HOUR_IN_SECONDS);
    }
}
