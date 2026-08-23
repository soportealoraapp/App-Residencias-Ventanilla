<?php declare(strict_types=1);

namespace Config;

use CodeIgniter\Database\Config;

class Database extends Config
{
    public string $filesPath = APPPATH . 'Database' . DIRECTORY_SEPARATOR;

    public string $defaultGroup = 'default';

    public array $default = [
        'DSN'          => '',
        'hostname'     => 'localhost',
        'username'     => 'root',
        'password'     => '',
        'database'     => 'database.sqlite',
        'DBDriver'     => 'SQLite3',
        'DBPrefix'     => '',
        'pConnect'     => false,
        'DBDebug'      => true,
        'charset'      => 'utf8mb4',
        'DBCollat'     => 'utf8mb4_general_ci',
        'swapPre'      => '',
        'encrypt'      => false,
        'compress'     => false,
        'strictOn'     => false,
        'failover'     => [],
        'port'         => 3306,
        'numberNative' => false,
        'foundRows'    => false,
        'foreignKeys'  => true,
        'busyTimeout'  => 1000,
    ];

    public array $tests = [
        'DSN'         => '',
        'hostname'    => '127.0.0.1',
        'username'    => '',
        'password'    => '',
        'database'    => ':memory:',
        'DBDriver'    => 'SQLite3',
        'DBPrefix'    => 'db_',
        'pConnect'    => false,
        'DBDebug'     => true,
        'charset'     => 'utf8',
        'DBCollat'    => '',
        'swapPre'     => '',
        'encrypt'     => false,
        'compress'    => false,
        'strictOn'    => false,
        'failover'    => [],
        'port'        => 3306,
        'foreignKeys' => true,
        'busyTimeout' => 1000,
    ];

    public function __construct()
    {
        parent::__construct();

        if (getenv('database.default.hostname')) {
            $this->default['hostname'] = getenv('database.default.hostname');
        }
        if (getenv('database.default.database')) {
            $this->default['database'] = getenv('database.default.database');
        }
        if (getenv('database.default.username')) {
            $this->default['username'] = getenv('database.default.username');
        }
        if (getenv('database.default.password') !== false) {
            $this->default['password'] = getenv('database.default.password');
        }
        if (getenv('database.default.DBDriver')) {
            $this->default['DBDriver'] = getenv('database.default.DBDriver');
        }
        if (getenv('database.default.DBPrefix') !== false) {
            $this->default['DBPrefix'] = getenv('database.default.DBPrefix');
        }
        if (getenv('database.default.port')) {
            $this->default['port'] = (int) getenv('database.default.port');
        }
        if (getenv('database.default.foreignKeys') !== false) {
            $this->default['foreignKeys'] = filter_var(getenv('database.default.foreignKeys'), FILTER_VALIDATE_BOOLEAN);
        }
        if (getenv('database.default.busyTimeout') !== false) {
            $this->default['busyTimeout'] = (int) getenv('database.default.busyTimeout');
        }
    }
}
