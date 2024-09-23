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

    protected $onDeleteQueryCallback;

    public function __construct($source, $options = [])
    {
        $defaults = [
            'cacheDuration' => null,
            'remotePrimaryKey' => null
        ];

        $this->id = hash('sha256', $source);
        $this->options = array_merge($defaults, $options);

        $this->source = $source;
        $this->database = new Database($this->options['database'] ?? []);
        $this->loader = new DataLoader;
    }

    public function setup()
    {
        if ($this->onCreateQueryCallback) {
            $this->database->exec(call_user_func($this->onCreateQueryCallback));
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

        $this->process($data);

        $cacheResult && $cache->put($this->id, true, $this->options['cacheDuration']);
    }

    protected function process($data) 
    {
        $this->insertOrUpdate($data);
        $this->delete($data);
    }

    protected function insertOrUpdate($data) 
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

    protected function delete($data) 
    {
        if (empty($this->onDeleteQueryCallback)) {
            return;
        }
        if (empty($this->options['remotePrimaryKey'])) {
            throw new \Exception('`remotePrimaryKey` field is required');
        }
        $key = $this->options['remotePrimaryKey'];

        foreach ($data as $item) {
            $ids[] = $item[$key] ?? null;
        }
        $ids = array_filter(array_unique($ids));

        if (!$ids) {
            return;
        }
        $bindings = implode(', ', array_fill(0, count($ids), '?'));
        $query = call_user_func($this->onDeleteQueryCallback, $key, $bindings);

        if (!$query) {
            return;
        }
        $this->database
        ->prepare($query)
        ->execute($ids);
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

    public function onDeleteQuery($callback) 
    {
        $this->onDeleteQueryCallback = $callback;
    }
}