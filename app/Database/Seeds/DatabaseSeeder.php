<?php declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call('RolesSeeder');
        $this->call('UsuariosDemoSeeder');
        $this->call('ConcesionesSeeder');
        $this->call('TarifasSeeder');
    }
}
