<?php

class Mandrill_Init {
    
    public $apikey;
    public $ch;
    public $root = 'http://mandrillapp.com/api/1.0';
    public $debug = false;

    public static $error_map = array(
        "ValidationError"       => "Mandrill_Error_ValidationError",
        "Invalid_Key"           => "Mandrill_Error_InvalidKey",
        "Unknown_Template"      => "Mandrill_Error_UnknownTemplate",
        "Invalid_Tag_Name"      => "Mandrill_Error_InvalidTagName",
        "Invalid_Reject"        => "Mandrill_Error_InvalidReject",
        "Unknown_Sender"        => "Mandrill_Error_UnknownSender",
        "Unknown_Url"           => "Mandrill_Error_UnknownUrl",
        "Invalid_Template"      => "Mandrill_Error_InvalidTemplate",
        "Unknown_Webhook"       => "Mandrill_Error_UnknownWebhook",
        "Unknown_InboundDomain" => "Mandrill_Error_UnknownInboundDomain"
    );

    public function __construct($apikey=null) {
        if(!$apikey) $apikey = getenv('Mandrill_APIKEY');
        if(!$apikey) $apikey = $this->readConfigs();
        if(!$apikey) throw new Mandrill_Error('You must provide a Mandrill API key');
        $this->apikey = $apikey;

        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_USERAGENT, 'Mandrill-PHP/1.0.17');
        curl_setopt($this->ch, CURLOPT_POST, true);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->ch, CURLOPT_HEADER, false);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 600);

        $this->root = rtrim($this->root, '/') . '/';

        $this->templates = new Mandrill_Class_Templates($this);
        $this->users     = new Mandrill_Class_Users($this);
        $this->rejects   = new Mandrill_Class_Rejects($this);
        $this->inbound   = new Mandrill_Class_Inbound($this);
        $this->tags      = new Mandrill_Class_Tags($this);
        $this->messages  = new Mandrill_Class_Messages($this);
        $this->internal  = new Mandrill_Class_Internal($this);
        $this->urls      = new Mandrill_Class_Urls($this);
        $this->webhooks  = new Mandrill_Class_Webhooks($this);
        $this->senders   = new Mandrill_Class_Senders($this);
    }

    public function __destruct() {
        curl_close($this->ch);
    }

    public function call($url, $params) {
        $params['key'] = $this->apikey;
        $params = json_encode($params);
        $ch = $this->ch;

        curl_setopt($ch, CURLOPT_URL, $this->root . $url . '.json');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_VERBOSE, $this->debug);

        $start = microtime(true);
        $this->log('Call to ' . $this->root . $url . '.json: ' . $params);
        if($this->debug) {
            $curl_buffer = fopen('php://memory', 'w+');
            curl_setopt($ch, CURLOPT_STDERR, $curl_buffer);
        }

        $response_body = curl_exec($ch);
        $info = curl_getinfo($ch);
        $time = microtime(true) - $start;
        if($this->debug) {
            rewind($curl_buffer);
            $this->log(stream_get_contents($curl_buffer));
            fclose($curl_buffer);
        }
        $this->log('Completed in ' . number_format($time * 1000, 2) . 'ms');
        $this->log('Got response: ' . $response_body);

        if(curl_error($ch)) {
            throw new Mandrill_Error_HttpError("API call to $url failed: " . curl_error($ch));
        }
        $result = json_decode($response_body, true);
        if($result === null) throw new Mandrill_Error('We were unable to decode the JSON response from the Mandrill API: ' . $response_body);
        
        if(floor($info['http_code'] / 100) >= 4) {
            throw $this->castError($result);
        }

        return $result;
    }

    public function readConfigs() {
        $paths = array('~/.mandrill.key', '/etc/mandrill.key');
        foreach($paths as $path) {
            if(file_exists($path)) {
                $apikey = trim(file_get_contents($path));
                if($apikey) return $apikey;
            }
        }
        return false;
    }

    public function castError($result) {
        if($result['status'] !== 'error' || !$result['name']) throw new Mandrill_Error('We received an unexpected error: ' . json_encode($result));

        $class = (isset(self::$error_map[$result['name']])) ? self::$error_map[$result['name']] : 'Mandrill_Error';
        return new $class($result['message'], $result['code']);
    }

    public function log($msg) {
        if($this->debug) error_log($msg);
    }
}


