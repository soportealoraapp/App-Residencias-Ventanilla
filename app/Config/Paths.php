<?php declare(strict_types=1);

namespace Config;

class Paths
{
    public string $systemDirectory = __DIR__ . '/../../vendor/codeigniter4/framework/system';

    public string $appDirectory = __DIR__ . '/..';

    public string $writableDirectory = __DIR__ . '/../../writable';

    public string $testsDirectory = __DIR__ . '/../../tests';

    public string $viewDirectory = __DIR__ . '/../Views';

    public function __construct()
    {
        if ($this->isVercel()) {
            $this->writableDirectory = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'writable';
            $this->ensureWritableDirs();
        }
    }

    private function isVercel(): bool
    {
        $vercel = getenv('VERCEL');
        if (is_string($vercel) && ($vercel === '1' || strtolower($vercel) === 'true')) {
            return true;
        }

        $vercelEnv = getenv('VERCEL_ENV');
        if (is_string($vercelEnv) && $vercelEnv !== '') {
            return true;
        }

        return false;
    }

    private function ensureWritableDirs(): void
    {
        $dirs = [
            $this->writableDirectory,
            $this->writableDirectory . DIRECTORY_SEPARATOR . 'session',
            $this->writableDirectory . DIRECTORY_SEPARATOR . 'logs',
            $this->writableDirectory . DIRECTORY_SEPARATOR . 'cache',
            $this->writableDirectory . DIRECTORY_SEPARATOR . 'uploads',
            $this->writableDirectory . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'documentos',
            $this->writableDirectory . DIRECTORY_SEPARATOR . 'debugbar',
        ];

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
        }
    }
}
