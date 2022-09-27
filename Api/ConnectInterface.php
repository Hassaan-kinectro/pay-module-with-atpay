<?php

namespace Pay\WithAtPay\Api;

interface ConnectInterface{

    /**
     * connect Api
     * 
     * @param string $name
     * @return string
     */
    public function connect($name);
}