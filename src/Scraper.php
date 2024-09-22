<?php

namespace Tunnela\DraiviCodingChallenge;

class Scraper
{
    protected $id;

    protected $database;

    protected $loader;

    protected $source;

    protected $onLoadCallback;

    protected $onSetupCallback;

    protected $onCreateQueryCallback;

    protected $onInsertQueryCallback;

    public function __construct($source, $options = [])
    {
        $defaults = [
            'cacheDuration' => null
        ];

        $this->id = hash('sha256', $source);
        $this->options = array_merge($defaults, $options);

        $this->source = $source;
        $this->database = new Database($this->options['database'] ?? []);
        $this->loader = new DataLoader;
    }

    public function setup()
    {
        if (!empty($this->options['database']['create'])) {
            $this->database->exec($this->options['database']['create']);
        }
    }

    public function run()
    {        
        $this->setup();

        $cacheResult = $this->options['cacheDuration'] !== null;

        if ($cacheResult && empty($this->options['cachePath'])) {
            throw new \Exception('`cacheDuration` has been defined, but `cachePath` is missing.');
        }
        $cache = $cacheResult ? new Cache($this->options['cachePath']) : null;

        if ($cacheResult && $cache->has($this->id, $this->options['cacheDuration'])) {
            return false;
        }
        $data = $this->loader->load($this->source);
        $data = $this->onLoadCallback ? call_user_func($this->onLoadCallback, $data, $cache) : $data;

        $this->save($data);

        $cacheResult && $cache->put($this->id, true, $this->options['cacheDuration']);
    }

    public function save($data) 
    {
        if (empty($this->onInsertQueryCallback)) {
            return;
        }
        $possibleKeys = [];

        foreach ($data as $item) {
            foreach ($item as $key => $value) {
                $possibleKeys[$key] = true;
            }
        }
        $keys = array_keys($possibleKeys);

        $bindings = implode(', ', array_fill(0, count($keys), '?'));
        $defaults = array_flip($keys);
        $fields = implode(', ', $keys);

        foreach (array_chunk($data, 50) as $items) {
            $this->database->beginTransaction();

            foreach ($items as $item) {
                $query = call_user_func($this->onInsertQueryCallback, $fields, $bindings);

                if (!$query) {
                    continue;
                }
                $this->database
                ->prepare($query)
                ->execute(array_values(array_merge($defaults, $item)));
            }
            $this->database->commit();
        }
    }

    public function getId() 
    {
        return $this->id;
    }

    public function onLoad($callback) 
    {
        $this->onLoadCallback = $callback;
    }

    public function onSetup($callback) 
    {
        $this->onSetupCallback = $callback;
    }

    public function onCreateQuery($callback) 
    {
        $this->onCreateQueryCallback = $callback;
    }

    public function onInsertQuery($callback) 
    {
        $this->onInsertQueryCallback = $callback;
    }
}