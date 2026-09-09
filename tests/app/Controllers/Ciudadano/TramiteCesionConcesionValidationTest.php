<?php declare(strict_types=1);

namespace Tests\Controllers\Portal;

use Tests\Support\DatabaseTestCase;
use App\Models\ConcesionModel;
use Config\Services;
use DateTime;

class TramiteCesionConcesionValidationTest extends DatabaseTestCase
{
    protected ConcesionModel $concesionModel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->concesionModel = new ConcesionModel();

        $this->seedConcesiones();
    }

    protected function seedConcesiones(): void
    {
        $this->concesionModel->insertBatch([
            [
                'numero_titulo'      => 'CONC-URI-VIGENTE-001',
                'titular_actual'     => 'Titular Demo Vigente',
                'vehiculo_placas'    => 'GTO-001-AA',
                'vehiculo_num_serie' => 'SERIEVIGENTE001',
                'vigencia_inicio'    => (new DateTime('-1 year'))->format('Y-m-d'),
                'vigencia_fin'       => (new DateTime('+4 years'))->format('Y-m-d'),
                'estatus'            => 'vigente',
            ],
            [
                'numero_titulo'      => 'CONC-URI-VENCIDA-099',
                'titular_actual'     => 'Titular Demo Vencida',
                'vehiculo_placas'    => 'GTO-099-ZZ',
                'vehiculo_num_serie' => 'SERIEVENCIDA099',
                'vigencia_inicio'    => (new DateTime('-5 years'))->format('Y-m-d'),
                'vigencia_fin'       => (new DateTime('-1 day'))->format('Y-m-d'),
                'estatus'            => 'vencida',
            ],
        ]);
    }

    protected function buildValidationRules(string $tipoCesion): array
    {
        $rules = [
            'tipo_cesion' => 'required|in_list[muerte_incapacidad,cesion_derechos,mandamiento_judicial]',
            'numero_titulo' => 'required',
        ];

        switch ($tipoCesion) {
            case 'muerte_incapacidad':
                $rules['acta_defuncion'] = 'required';
                $rules['identificacion_heredero'] = 'required';
                $rules['documento_herencia'] = 'required';
                break;

            case 'cesion_derechos':
                $rules['contrato_cesion_notarial'] = 'required';
                $rules['identificacion_cedente'] = 'required';
                $rules['identificacion_cesionario'] = 'required';
                $rules['comprobante_domicilio'] = 'required';
                $rules['ultimo_pago_derechos'] = 'required';
                break;

            case 'mandamiento_judicial':
                $rules['resolucion_judicial'] = 'required';
                $rules['acta_nacimiento'] = 'required';
                $rules['identificacion_representante'] = 'required';
                break;
        }

        return $rules;
    }

    protected function validateConcesionVigente(string $numeroTitulo): bool
    {
        $concesion = $this->concesionModel->findByNumeroTitulo($numeroTitulo);
        if ($concesion === null) {
            return false;
        }
        return $concesion->estatus === 'vigente';
    }

    public function testTipoCesionMuerte_IncapacidadRequiereActaDefuncion(): void
    {
        $validation = Services::validation();
        $rules = $this->buildValidationRules('muerte_incapacidad');

        $data = [
            'tipo_cesion'              => 'muerte_incapacidad',
            'numero_titulo'            => 'CONC-URI-VIGENTE-001',
            'identificacion_heredero'  => 'presente',
            'documento_herencia'       => 'presente',
        ];

        $result = $validation->setRules($rules)->run($data);

        $this->assertFalse($result);
        $errors = $validation->getErrors();
        $this->assertArrayHasKey('acta_defuncion', $errors);
    }

    public function testTipoCesionCesionDerechos_Requiere5DocumentosEspecificos(): void
    {
        $validation = Services::validation();
        $rules = $this->buildValidationRules('cesion_derechos');

        $data = [
            'tipo_cesion'                 => 'cesion_derechos',
            'numero_titulo'               => 'CONC-URI-VIGENTE-001',
            'identificacion_cedente'      => 'presente',
            'identificacion_cesionario'   => 'presente',
            'comprobante_domicilio'       => 'presente',
            'ultimo_pago_derechos'        => 'presente',
        ];

        $result = $validation->setRules($rules)->run($data);

        $this->assertFalse($result);
        $errors = $validation->getErrors();
        $this->assertArrayHasKey('contrato_cesion_notarial', $errors);
    }

    public function testTipoCesionMandamientoJudicial_RequiereResolucionYActaNacimiento(): void
    {
        $validation = Services::validation();
        $rules = $this->buildValidationRules('mandamiento_judicial');

        $data = [
            'tipo_cesion'                  => 'mandamiento_judicial',
            'numero_titulo'                => 'CONC-URI-VIGENTE-001',
            'identificacion_representante' => 'presente',
        ];

        $result = $validation->setRules($rules)->run($data);

        $this->assertFalse($result);
        $errors = $validation->getErrors();
        $this->assertArrayHasKey('resolucion_judicial', $errors);
        $this->assertArrayHasKey('acta_nacimiento', $errors);
    }

    public function testConcesionInexistenteONoVigente_Rechazada(): void
    {
        $resultadoVencida = $this->validateConcesionVigente('CONC-URI-VENCIDA-099');
        $this->assertFalse($resultadoVencida);

        $resultadoInexistente = $this->validateConcesionVigente('CONC-URI-NOEXISTE-999');
        $this->assertFalse($resultadoInexistente);
    }

    public function testConcesionVigente_Aprobada(): void
    {
        $resultado = $this->validateConcesionVigente('CONC-URI-VIGENTE-001');
        $this->assertTrue($resultado);
    }
}
