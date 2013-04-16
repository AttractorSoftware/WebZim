<?php

class HttpClient {
    // Request vars
    protected  $host;
    protected $username;
    protected $password;
    protected $path;
    protected $status;
    protected $content;
    protected $referer;

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
        $this->doRequest($data);
    }

    public function setAutherization($username, $password)
    {
        $this->username = $username;
        $this->password = $password;
    }


    public function doRequest($data = array())
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
        if(count($data))
        {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        if($this->referer)
        {
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_REFERER, $this->referer);
        }
        // $output contains the output string
        $output = curl_exec($ch);
        $this->referer = $this->host.$this->path;
        $this->setContent($output);
        $this->setStatus(curl_getinfo($ch,CURLINFO_HTTP_CODE));
        // close curl resource to free up system resources
        curl_close($ch);
    }

}
