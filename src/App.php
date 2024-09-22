<?php

namespace Tunnela\DraiviCodingChallenge;

class App
{
    protected $database;

    protected $routes = [];

    public function __construct($options = [])
    {
        $this->options = array_merge($options);
        $this->database = new Database($this->options['database'] ?? []);
    }

    public function run() 
    {
        foreach ($this->routes as $route) {
            if (($matches = $this->matches($route)) === false) {
                continue;
            }
            $matches['app'] = $this;
            $matches['database'] = $this->database;
            $matches['data'] = (object) $this->data();

            // get args in the route callback func
            $argumentNames = $this->argumentNames($route['callback']);

            // let's make each arg null by default
            $args = array_fill_keys($argumentNames, null);

            // based on what args route callback 
            // func wants, we'll inject just them
            $args = array_merge(
                $args,
                array_intersect_key(
                    $matches,
                    $args
                )
            );

            $callback = \Closure::bind($route['callback'], $this, null);

            $result = call_user_func_array($callback, $args);

            if ($result === null) {
                continue;
            }
            echo $result;
            exit;
        }
        http_response_code(404);
    }

    public function database() 
    {
        return $this->database;
    }

    public function route($path, $callback, $options = []) 
    {
        $this->routes[] = [
            'regex' => $this->regexify($path),
            'callback' => $callback
        ] + $options;
    }

    public function get($path, $callback) 
    {
        $this->route($path, $callback, ['method' => 'GET']);
    }

    public function post($path, $callback) 
    {
        $this->route($path, $callback, ['method' => 'POST']);
    }

    public function put($path, $callback) 
    {
        $this->route($path, $callback, ['method' => 'PUT']);
    }

    public function delete($path, $callback) 
    {
        $this->route($path, $callback, ['method' => 'DELETE']);
    }

    public function view($name, $status = 200)
    {
        http_response_code($status);

        header('Content-Type: text/html');

        return file_get_contents($this->options['viewPath'] . '/' . $name . '.html');
    }

    public function json($data, $status = 200)
    {
        http_response_code($status);
        
        header('Content-Type: application/json');

        return json_encode($data, JSON_PRETTY_PRINT);
    }

    protected function method() 
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    protected function headers() 
    {
        return getallheaders();
    }

    protected function data()
    {
        $method = $this->method();
        $headers = $this->headers();

        if ($method == 'GET') {
            return $_GET;
        } else if ($method == 'POST' || $method == 'PUT') {
            $input = file_get_contents('php://input');

            if ($headers['Content-Type'] == 'application/json') {
                return json_decode($input, true);
            }
            parse_str($input, $vars);

            return $vars ?: [];
        }
        return [];
    }

    protected function regexify($path)
    {
        $trimmed = rtrim($path, '/');

        return preg_replace_callback('#\{([a-z0-9_\-]+)\}#', function($matches) {
            return '(?P<' . $matches[1] . '>[^/]+)';
        }, $trimmed);
    }

    protected function matches($route)
    {
        $requestUri = rtrim($_SERVER['REQUEST_URI'], '/');
        $isMatch = preg_match('#^' . $route['regex'] . '$#', $requestUri, $matches);
        $isValidMethod = !isset($route['method']) || $route['method'] == $_SERVER['REQUEST_METHOD'];

        if (!$isValidMethod || !$isMatch) {
            return false;
        }
        return $matches ?: [];
    }

    protected function argumentNames($func) {
        $reflection = new \ReflectionFunction($func);
        $result = [];

        foreach ($reflection->getParameters() as $param) {
            $result[] = $param->name;   
        }
        return $result;
    }
}
