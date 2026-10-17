<?php declare(strict_types=1);

namespace App\Libraries;

use App\Models\AgenteTokenModel;
use App\Models\AgenteTransitoModel;
use CodeIgniter\HTTP\RequestInterface;
use WeakMap;

/**
 * Emite y valida los tokens Bearer de la app móvil de agentes de tránsito.
 *
 * El token es aleatorio (256 bits) y solo se guarda su hash SHA-256, de modo
 * que una copia de la base de datos no permite suplantar a un agente.
 */
class AgenteTokenService
{
    /** Vigencia del token: un turno de trabajo. */
    public const TTL_SEGUNDOS = 12 * 3600;

    /** @var WeakMap<RequestInterface, object>|null Agente ya validado por solicitud. */
    private static ?WeakMap $autenticados = null;

    /**
     * @return array{token: string, expira_en: string}
     */
    public function emitir(int $agenteId): array
    {
        $token = bin2hex(random_bytes(32));
        $ahora = time();
        $expiraEn = date('Y-m-d H:i:s', $ahora + self::TTL_SEGUNDOS);

        $model = new AgenteTokenModel();
        // Limpieza de tokens vencidos o revocados del mismo agente.
        $model->where('agente_id', $agenteId)
            ->groupStart()
                ->where('expira_en <', date('Y-m-d H:i:s', $ahora))
                ->orWhere('revocado_en IS NOT NULL')
            ->groupEnd()
            ->delete();

        $model->insert([
            'agente_id'  => $agenteId,
            'token_hash' => self::hash($token),
            'expira_en'  => $expiraEn,
        ]);

        return ['token' => $token, 'expira_en' => $expiraEn];
    }

    /**
     * Devuelve el agente activo dueño del token Bearer de la solicitud, o null.
     */
    public function agenteDeSolicitud(RequestInterface $request): ?object
    {
        self::$autenticados ??= new WeakMap();
        if (isset(self::$autenticados[$request])) {
            return self::$autenticados[$request];
        }

        $token = self::extraerBearer($request->getHeaderLine('Authorization'));
        if ($token === null) {
            return null;
        }

        $tokenModel = new AgenteTokenModel();
        $registro = $tokenModel->where('token_hash', self::hash($token))->first();
        $ahora = date('Y-m-d H:i:s');
        if ($registro === null || $registro->revocado_en !== null || $registro->expira_en <= $ahora) {
            return null;
        }

        $agente = (new AgenteTransitoModel())->find((int) $registro->agente_id);
        if ($agente === null || (int) $agente->activo !== 1) {
            return null;
        }

        $tokenModel->update($registro->id, ['ultimo_uso_en' => $ahora]);
        self::$autenticados[$request] = $agente;

        return $agente;
    }

    private static function extraerBearer(string $header): ?string
    {
        if (preg_match('/^Bearer\s+([A-Fa-f0-9]{64})$/', trim($header), $m) !== 1) {
            return null;
        }

        return strtolower($m[1]);
    }

    private static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
