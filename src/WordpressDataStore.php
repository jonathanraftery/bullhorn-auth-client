<?php
	
namespace jonathanraftery\Bullhorn;

class WordpressDataStore implements DataStore
{	
	const BH_OPTION_NAME = "bullhorn-datastore";
	
    public function store($key, $value)
    {
        $data = $this->readDataFile();
        $data->tokens->$key = $value;
        
        update_option( self::BH_OPTION_NAME, json_encode($data) );
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
	    $data = get_option( self::BH_OPTION_NAME );
	    
        if ($data)
            return json_decode($data);
        else
            return json_decode('{"tokens":{}}');
    }

}
