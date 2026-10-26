<?php declare(strict_types=1);
namespace Tests\Libraries;
use App\Libraries\CalificacionBoletaException;
use App\Libraries\CalificarBoletaService;
use App\Libraries\CobroException;
use App\Libraries\CobroService;
use App\Libraries\ResolverMontoInfraccionService;
use App\Models\BoletaInfraccionModel;
use App\Models\UserModel;
use Tests\Support\DatabaseTestCase;
class CalificarBoletaServiceTest extends DatabaseTestCase
{
    private int $juezId;
    protected function setUp(): void
    {
        parent::setUp();
        $this->db->table('parametros_sistema')->where('clave', 'valor_uma_vigente')->delete();
        $this->db->table('parametros_sistema')->insert(['clave'=>'valor_uma_vigente','valor'=>'117.31','descripcion'=>'Prueba']);
        $this->juezId=(int)(new UserModel())->insert(['username'=>'juez_cob10','email'=>'juez-cob10@example.test','password_hash'=>'x','nombre_completo'=>'Juez COB-10','activo'=>1]);
    }
    private function faltas(): array
    {
        return [
            ['id'=>'1','fundamentoLegal'=>'Art. 1','descripcion'=>'Falta uno','categoria'=>'GENERAL','montoMinUma'=>2,'montoMaxUma'=>4],
            ['id'=>'2','fundamentoLegal'=>'Art. 2','descripcion'=>'Falta dos','categoria'=>'GENERAL','montoMinUma'=>3,'montoMaxUma'=>6],
        ];
    }
    private function boleta(array|string|null $faltas=null,string $estado='recibida'): int
    {
        return (int)(new BoletaInfraccionModel())->insert([
            'folio'=>'COB10-'.bin2hex(random_bytes(5)),'agente_placa'=>'AGT-C10','agente_nombre'=>'Agente de prueba',
            'fecha_infraccion'=>'2026-10-20','hora_infraccion'=>'10:00:00','lugar'=>'Uriangato','vehiculo_placas'=>'ABC-123',
            'faltas_json'=>is_array($faltas)?json_encode($faltas,JSON_UNESCAPED_UNICODE):$faltas,'estado'=>$estado,
        ]);
    }
    public function testCalculaVariasFaltasConRedondeoCentralizado(): void
    {
        $c=(new ResolverMontoInfraccionService())->calcularCalificacion($this->faltas(),['2.50','4.00']);
        $this->assertSame(117.31,$c['valor_uma']); $this->assertSame(6.5,$c['total_uma']); $this->assertSame(762.52,$c['monto_base']);
        $this->assertSame(293.28,$c['detalles'][0]['subtotal_pesos']); $this->assertSame(469.24,$c['detalles'][1]['subtotal_pesos']);
    }
    public function testRechazaOmisionesIndicesYRangosInvalidos(): void
    {
        $r=new ResolverMontoInfraccionService();
        foreach([['2.00'],[0=>'2.00',2=>'3.00'],['1.99','3.00'],['2.00','6.01'],['2.001','3.00']] as $s){
            try{$r->calcularCalificacion($this->faltas(),$s);$this->fail('Debió rechazarse.');}catch(\RuntimeException){$this->addToAssertionCount(1);}
        }
    }
    public function testLeeJsonDirectoYDobleSerializado(): void
    {
        $s=new CalificarBoletaService(); $json=json_encode($this->faltas(),JSON_UNESCAPED_UNICODE); $m=new BoletaInfraccionModel();
        $this->assertCount(2,$s->faltasDeBoleta($m->find($this->boleta($json))));
        $this->assertCount(2,$s->faltasDeBoleta($m->find($this->boleta(json_encode($json)))));
    }
    public function testRechazaJsonDanadoSinPersistirloEnJsonb(): void
    {
        $boleta=(object)['faltas_json'=>'{mal json'];
        $this->expectException(CalificacionBoletaException::class);
        $this->expectExceptionMessage('faltas de la boleta están dañadas');
        (new CalificarBoletaService())->faltasDeBoleta($boleta);
    }
    public function testRechazaNombreFaltanteConJsonValido(): void
    {
        $this->expectException(CalificacionBoletaException::class); $this->expectExceptionMessage('nombre del infractor');
        (new CalificarBoletaService())->calificar($this->boleta($this->faltas()),'   ',['2','3'],$this->juezId);
    }
    public function testRechazaMarcadorDeConductorAusente(): void
    {
        $this->expectException(CalificacionBoletaException::class);
        (new CalificarBoletaService())->calificar($this->boleta($this->faltas()), 'Conductor Ausente', ['2','3'], $this->juezId);
    }
    public function testNoVinculaUnCobroPrevioComoSiFueraLaCalificacion(): void
    {
        $id=$this->boleta($this->faltas());
        (new CobroService())->crearDesdeBoleta($id,500,$this->juezId,'Persona Previa');
        try{(new CalificarBoletaService())->calificar($id,'Persona Prueba',['2','3'],$this->juezId);$this->fail('Debió rechazarse.');}
        catch(CalificacionBoletaException){$this->addToAssertionCount(1);}
        $this->assertSame(0,$this->db->table('boleta_calificaciones')->where('boleta_id',$id)->countAllResults());
        $this->assertSame('recibida',$this->db->table('boletas_infracciones')->where('id',$id)->get()->getRow()->estado);
    }

    public function testCalificacionCobroMovimientoYEstadoSeCreanJuntos(): void
    {
        $id=$this->boleta($this->faltas()); $r=(new CalificarBoletaService())->calificar($id,'María López',['2.50','4'],$this->juezId);
        $q=$this->db->table('boleta_calificaciones')->where('id',$r['calificacion_id'])->get()->getRow();
        $c=$this->db->table('cobros')->where('id',$r['cobro_id'])->get()->getRow();
        $this->assertSame('María López',$q->nombre_infractor); $this->assertEquals(762.52,(float)$q->monto_base);
        $this->assertSame(2,$this->db->table('boleta_calificacion_detalles')->where('calificacion_id',$q->id)->countAllResults());
        $this->assertSame('pendiente',$c->estatus); $this->assertEquals(762.52,(float)$c->monto_base);
        $this->assertSame(1,$this->db->table('cobro_movimientos')->where('cobro_id',$c->id)->countAllResults());
        $b=$this->db->table('boletas_infracciones')->where('id',$id)->get()->getRow(); $this->assertSame('calificada',$b->estado); $this->assertSame('María López',$b->infractor_nombre);
    }
    public function testDobleEnvioNoDuplica(): void
    {
        $id=$this->boleta($this->faltas()); $s=new CalificarBoletaService(); $s->calificar($id,'Persona Prueba',['2','3'],$this->juezId);
        try{$s->calificar($id,'Persona Prueba',['2','3'],$this->juezId);$this->fail('Debió rechazarse.');}catch(CalificacionBoletaException){$this->addToAssertionCount(1);}
        $this->assertSame(1,$this->db->table('boleta_calificaciones')->where('boleta_id',$id)->countAllResults());
        $this->assertSame(1,$this->db->table('cobros')->where('boleta_id',$id)->countAllResults());
    }
    public function testFalloDelCobroRevierteTodo(): void
    {
        $id=$this->boleta($this->faltas());
        $fallo=new class extends CobroService{public function crearDesdeBoleta(int $boletaId,float $montoBase,?int $usuarioId=null,?string $nombrePagador=null):int{throw new CobroException('Fallo simulado.');}};
        $s=new CalificarBoletaService(null,$fallo,$this->db);
        try{$s->calificar($id,'Persona Prueba',['2','3'],$this->juezId);$this->fail('Debió fallar.');}catch(CalificacionBoletaException){$this->addToAssertionCount(1);}
        $this->assertSame(0,$this->db->table('boleta_calificaciones')->where('boleta_id',$id)->countAllResults());
        $this->assertSame(0,$this->db->table('boleta_calificacion_detalles')->countAllResults());
        $this->assertSame(0,$this->db->table('cobros')->where('boleta_id',$id)->countAllResults());
        $this->assertSame('recibida',$this->db->table('boletas_infracciones')->where('id',$id)->get()->getRow()->estado);
    }
}
