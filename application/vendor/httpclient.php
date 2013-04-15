<?php

class HttpClient {
    // Request vars
    protected  $host;
    protected $username;
    protected $password;
    protected $path;
    protected $status;
    protected $content;

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getContent()
    {
        return $this->content;
    }


    function HttpClient($host, $port=80) {
        $this->host = $host;
        $this->port = $port;
    }
    public function get($path, $data = false) {
        $this->path = $path;
        $this->doRequest();
    }
    public function post($path, $data) {
        $this->path = $path;
        $this->doRequest();
    }

    public function setAutherization($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }


    public function doRequest()
    {
        $ch = curl_init();
        // set url
        curl_setopt($ch, CURLOPT_URL, $this->host.$this->path);
        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if($this->username && $this->password)
        {
            curl_setopt($ch, CURLOPT_USERPWD, $this->username.':'.$this->password);
        }
        // $output contains the output string
        $output = curl_exec($ch);
        $this->setContent($output);
        $this->setStatus(curl_getinfo($ch,CURLINFO_HTTP_CODE));
        // close curl resource to free up system resources
        curl_close($ch);
    }

}

?>