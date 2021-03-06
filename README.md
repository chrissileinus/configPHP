# configPHP

A storage to hold the config of a app. It ist mainly static but as a Singleton I have integrated the interfaces `ArrayAccess`, `Serializable`, `JsonSerializable`, `IteratorAggregate` and `Traversable`.

## Why

There was a need for a small class that is able to read the config from serval inputs and files so that it is stored in a way that it is globally accessible with serval interfaces.

## Usage

```PHP
Config\Storage::integrate(
  // default values
  [
    'database' => [
      'host' => "localhost",
      'port' => 3306,
      'name' => "databasename",
      'user' => "username",
      'pass' => "fdsgsdgs",
    ],
    'log' => [
      'timezone' => "UTC",
      'console' => [
        'level' => Log\Level::NONE,
        'ignore' => [],
      ]
    ]
  ],
  // default value get replaced by values from config files.
  '/etc/App/*.yaml',
  '/etc/App/*.json'
);

$dbConfig = Config\Storage::getInstance()['database'];
$db = new PDO("mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['name']}", $dbConfig['user'], $dbConfig['pass']);
```
