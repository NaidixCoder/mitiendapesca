<?php
namespace App\Services\Billing\Arca;

/**
 * WSFEv1 (FE)
 * Implementación mínima para emitir Factura C (Monotributo) con CAE.
 *
 * Nota: para RI (A/B) necesitaremos también detalle de IVA (alícuotas) y/o tributos.
 */
final class WsfeService
{
    /** @param array<string,mixed> $cfg */
    public function __construct(private array $cfg) {}

    /**
     * @param array{token:string,sign:string} $auth
     */
    public function ultimoAutorizado(array $auth, int $ptoVta, int $cbteTipo): int
    {
        $client = $this->client();
        $resp = $client->FECompUltimoAutorizado([
            'Auth' => ['Token'=>$auth['token'], 'Sign'=>$auth['sign'], 'Cuit'=>(float)$this->cuit()],
            'PtoVta' => $ptoVta,
            'CbteTipo' => $cbteTipo,
        ]);
        $n = $resp->FECompUltimoAutorizadoResult->CbteNro ?? 0;
        return (int)$n;
    }

    /**
     * Consulta un comprobante previamente emitido.
     *
     * @param array{token:string,sign:string} $auth
     * @return array<string,mixed>
     */
    public function consultarComprobante(array $auth, int $ptoVta, int $cbteTipo, int $cbteNro): array
    {
        $client = $this->client();
        $resp = $client->FECompConsultar([
            'Auth' => ['Token'=>$auth['token'], 'Sign'=>$auth['sign'], 'Cuit'=>(float)$this->cuit()],
            'FeCompConsReq' => [
                'CbteTipo' => $cbteTipo,
                'PtoVta' => $ptoVta,
                'CbteNro' => $cbteNro,
            ],
        ]);

        // Resultado típico: FECompConsultarResult->ResultGet
        $res = $resp->FECompConsultarResult ?? null;
        $rg = $res->ResultGet ?? null;

        return [
            'raw' => $resp,
            'resultget' => $rg,
            'cbte_nro' => (int)($rg->CbteDesde ?? 0),
            'cbte_fch' => (string)($rg->CbteFch ?? ''),
            'cae' => (string)($rg->CodAutorizacion ?? ''),
            'cae_vto' => (string)($rg->FchVto ?? ''),
            'imp_total' => (float)($rg->ImpTotal ?? 0),
            'doc_tipo' => (int)($rg->DocTipo ?? 0),
            'doc_nro' => (int)($rg->DocNro ?? 0),
        ];
    }

    /**
     * Solicita CAE.
     * @param array{token:string,sign:string} $auth
     * @param array<string,mixed> $req
     * @return array<string,mixed>
     */
    public function solicitarCAE(array $auth, array $req): array
    {
        $client = $this->client();

        $payload = [
            'Auth' => ['Token'=>$auth['token'], 'Sign'=>$auth['sign'], 'Cuit'=>(float)$this->cuit()],
            'FeCAEReq' => $req,
        ];

        $resp = $client->FECAESolicitar($payload);
        $res = $resp->FECAESolicitarResult ?? null;
        if (!$res) throw new \RuntimeException('WSFE: respuesta vacía de FECAESolicitar.');

        // Resultado
        $cab = $res->FeCabResp ?? null;
        $det = $res->FeDetResp->FECAEDetResponse ?? null;
        if (is_array($det)) $det = $det[0] ?? null;

        $resultado = (string)($cab->Resultado ?? '');
        $cae = (string)($det->CAE ?? '');
        $caeVto = (string)($det->CAEFchVto ?? '');
        $cbteDesde = (int)($det->CbteDesde ?? 0);

        $obs = [];
        $obsNode = $det->Observaciones->Obs ?? null;
        if ($obsNode) {
            if (is_array($obsNode)) {
                foreach ($obsNode as $o) {
                    $obs[] = ['code'=>(string)($o->Code ?? ''), 'msg'=>(string)($o->Msg ?? '')];
                }
            } else {
                $obs[] = ['code'=>(string)($obsNode->Code ?? ''), 'msg'=>(string)($obsNode->Msg ?? '')];
            }
        }

        $err = [];
        $errNode = $res->Errors->Err ?? null;
        if ($errNode) {
            if (is_array($errNode)) {
                foreach ($errNode as $e) {
                    $err[] = ['code'=>(string)($e->Code ?? ''), 'msg'=>(string)($e->Msg ?? '')];
                }
            } else {
                $err[] = ['code'=>(string)($errNode->Code ?? ''), 'msg'=>(string)($errNode->Msg ?? '')];
            }
        }

        return [
            'resultado' => $resultado,
            'cae' => $cae,
            'cae_vto' => $caeVto,
            'cbte_nro' => $cbteDesde,
            'observaciones' => $obs,
            'errors' => $err,
            'raw' => $resp,
        ];
    }

    private function cuit(): string
    {
        $cuit = preg_replace('/\D+/', '', (string)($this->cfg['cuit'] ?? '')) ?? '';
        if ($cuit === '') throw new \RuntimeException('ARCA_CUIT no configurado.');
        return $cuit;
    }

    private function client(): \SoapClient
    {
        if (!class_exists('SoapClient')) {
            throw new \RuntimeException('Extensión SOAP no disponible en PHP.');
        }
        $env = (string)($this->cfg['env'] ?? 'homologacion');
        $wsdl = $env === 'produccion'
            ? 'https://servicios1.afip.gov.ar/wsfev1/service.asmx?WSDL'
            : 'https://wswhomo.afip.gov.ar/wsfev1/service.asmx?WSDL';

        return new \SoapClient($wsdl, [
            'soap_version' => SOAP_1_1,
            'exceptions' => true,
            'trace' => false,
            'cache_wsdl' => WSDL_CACHE_BOTH,
        ]);
    }
}
