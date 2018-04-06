<?php
namespace Infra;

use Symfony\Component\Yaml\Yaml;

class Config
{
    static protected $instance;

    protected $config;

    static public function getInstance()
    {
        if (!static::$instance instanceof self) {
            static::$instance = new self;
        }

        return static::$instance;
    }

    private function __construct()
    {
        $this->config = Yaml::parse(
            file_get_contents(__DIR__ . '/../../config/config.yaml')
        );
    }

    public function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $config = $this->config;
        foreach ($keys as $key) {
            if (!isset($config[$key])) {
                return $default;
            }

            $config = $config[$key];
        }

        return $config;
    }
}