<?php declare(strict_types=1);

namespace Tests\Support;

use CodeIgniter\Test\DatabaseTestCase as CIDatabaseTestCase;

class DatabaseTestCase extends CIDatabaseTestCase
{
    protected $DBGroup = 'tests';

    protected $migrate = true;

    protected $migrateOnce = false;

    protected $refresh = true;

    protected $namespace = null;

    protected $seed = false;

    protected $seedOnce = false;

    protected $basePath = APPPATH . 'Database' . DIRECTORY_SEPARATOR;
}
