<?php declare(strict_types=1);

namespace Tests\Support;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

class DatabaseTestCase extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $DBGroup = 'tests';

    protected $migrate = true;

    protected $migrateOnce = false;

    protected $refresh = true;

    protected $namespace = null;

    protected $seed = '';

    protected $seedOnce = false;

    protected $basePath = APPPATH . 'Database' . DIRECTORY_SEPARATOR;
}
