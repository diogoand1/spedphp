<?php

namespace SpedPHP\NFe;

use SpedPHP\Common\Certificate\Pkcs12;
use SpedPHP\Common\Soap;
use SpedPHP\Common\Exception;
use SpedPHP\Common\DateTime\DateTime;

/**
 * @category   SpedPHP
 * @package    SpedPHP\NFe
 * @copyright  Copyright (c) 2008-2014
 * @license    http://www.gnu.org/licenses/lesser.html LGPL v3
 * @author     Roberto L. Machado <linux.rlm@gamil.com>
 * @link       http://github.com/nfephp-org/spedphp for the canonical source repository
 */

if (!defined('PATH_ROOT')) {
    define('PATH_ROOT', dirname(realpath(__FILE__)).DIRECTORY_SEPARATOR);
}

class NFe
{
    public $modsoap = 'curl';
    public $amb = 'homologacao';
    public $sigla = '';
    
    public $enableSCAN = false;
    public $enableSVCAN = false;
    public $enableSVCRS = false;
    public $soapDebug = '';
    
    protected $nfeWs = 'nfeWS.xml';
    
    protected $oPkcs12;

    private $aliaslist = array('AC'=>'SVRS',
                               'AL'=>'SVRS',
                               'AM'=>'AM',
                               'AN'=>'AN',
                               'AP'=>'SVRS',
                               'BA'=>'BA',
                               'CE'=>'CE',
                               'DF'=>'SVRS',
                               'ES'=>'SVRS',
                               'GO'=>'GO',
                               'MA'=>'SVAN',
                               'MG'=>'MG',
                               'MS'=>'MS',
                               'MT'=>'MT',
                               'PA'=>'SVAN',
                               'PB'=>'SVRS',
                               'PE'=>'PE',
                               'PI'=>'SVAN',
                               'PR'=>'PR',
                               'RJ'=>'SVRS',
                               'RN'=>'SVRS',
                               'RO'=>'SVRS',
                               'RR'=>'SVRS',
                               'RS'=>'RS',
                               'SC'=>'SVRS',
                               'SE'=>'SVRS',
                               'SP'=>'SP',
                               'TO'=>'SVRS',
                               'SCAN'=>'SCAN',
                               'SVAN'=>'SVAN',
                               'SVRS'=>'SVRS',
                               'SVCAN'=>'SVCAN',
                               'SVCRS'=>'SVCRS',
                               'DPEC'=>'DPEC');

    private $cUFlist = array('AC'=>'12',
                             'AL'=>'27',
                             'AM'=>'13',
                             'AP'=>'16',
                             'BA'=>'29',
                             'CE'=>'23',
                             'DF'=>'53',
                             'ES'=>'32',
                             'GO'=>'52',
                             'MA'=>'21',
                             'MG'=>'31',
                             'MS'=>'50',
                             'MT'=>'51',
                             'PA'=>'15',
                             'PB'=>'25',
                             'PE'=>'26',
                             'PI'=>'22',
                             'PR'=>'41',
                             'RJ'=>'33',
                             'RN'=>'24',
                             'RO'=>'11',
                             'RR'=>'14',
                             'RS'=>'43',
                             'SC'=>'42',
                             'SE'=>'28',
                             'SP'=>'35',
                             'TO'=>'17',
                             'SVAN'=>'91');

    const URLPORTALNFE='http://www.portalfiscal.inf.br/nfe';
    
    public function __construct($cnpj, $certsdir, $pubKey, $priKey)
    {
        $this->oPkcs12 = new Pkcs12($certsdir, $cnpj, $pubKey, $priKey);
        
    }//fim __construct
    
    public function send()
    {
        
    }//fim send
    
    public function query()
    {
        
    }//fim query
    
    public function cancel()
    {
        
    }//fim cancel
    
    public function disabled()
    {
        
    }//fim disabled
    
    public function status()
    {
        
    }//fim status
    
    public function isOnline($sigla = '', $tpAmb = '', &$response = '')
    {
        if ($tpAmb == '1') {
            $ambiente = 'producao';
        } else {
            $ambiente = 'homologacao';
        }
        //recupera o numero da SEFAZ
        $cUF = $this->cUFlist[$sigla];
        //recupera os dados de acesso a SEFAZ
        $aURL = $this->loadSefaz($sigla, $ambiente);
        //identificação do serviço
        $servico = 'NfeStatusServico';
        //recuperação da versão
        $versao = $aURL[$servico]['version'];
        //recuperação da url do serviço
        $urlservico = $aURL[$servico]['URL'];
        //recuperação do método
        $metodo = $aURL[$servico]['method'];
        //montagem do namespace do serviço
        $namespace = URLPORTALNFE.'/wsdl/'.$servico.'2';
        //montagem do cabeçalho da comunicação SOAP
        //ATENÇÂO NESSAS MONTAGENS NÃO PODE HAVER ESPAÇOS ENTRE ><
        $cabec = '';
        $cabec .= '<nfeCabecMsg xmlns="'. $namespace . '"><cUF>'.$cUF.'</cUF>';
        $cabec .= '<versaoDados>'.$versao.'</versaoDados></nfeCabecMsg>';
        //montagem dos dados da mensagem SOAP
        $dados = '';
        $dados .= '<nfeDadosMsg xmlns="'.$namespace.'"><consStatServ xmlns="';
        $dados .= URLPORTALNFE.'" versao="'.$versao.'"><tpAmb>'.$tpAmb.'</tpAmb>';
        $dados .= '<cUF>'.$cUF.'</cUF><xServ>STATUS</xServ></consStatServ></nfeDadosMsg>';
        if ($this->modsoap == 'curl') {
            $soap = new Soap\CurlSoap($this->oPkcs12->priKeyFile, $this->oPkcs12->pubKeyFile);
            $returnSoap = $soap->send($urlservico, $namespace, $cabec, $dados, $metodo);
            $this->soapDebug = $soap->soapDebug;
            $soap = null;
        } else {
            $returnSoap = false;
        }
        if ($returnSoap === false) {
            //houve alguma falha na comunicaçao
            throw new Exception\RuntimeException($soap->error);
        }
        //tratar dados de retorno
        $doc = new \DOMDocument('1.0', 'utf-8'); //cria objeto DOM
        $doc->formatOutput = false;
        $doc->preserveWhiteSpace = false;
        $doc->loadXML($returnSoap, LIBXML_NOBLANKS | LIBXML_NOEMPTYTAG);
        $cStat = !empty($doc->getElementsByTagName('cStat')->item(0)->nodeValue) ?
            $doc->getElementsByTagName('cStat')->item(0)->nodeValue :
            '';
        if ($cStat == '') {
            $msg = "Não houve retorno Soap verifique a mensagem de erro e o debug!!";
            throw new Exception\RuntimeException($msg);
        } else {
            if ($cStat == '107') {
                $response['bStat'] = true;
            } else {
                $response['bStat'] = false;
            }
        }
        // tipo de ambiente
        $response['tpAmb'] = $doc->getElementsByTagName('tpAmb')->item(0)->nodeValue;
        // versão do aplicativo
        $response['verAplic'] = $doc->getElementsByTagName('verAplic')->item(0)->nodeValue;
        // Código da UF que atendeu a solicitação
        $response['cUF'] = $doc->getElementsByTagName('cUF')->item(0)->nodeValue;
        // status do serviço
        $response['cStat'] = $doc->getElementsByTagName('cStat')->item(0)->nodeValue;
        // tempo medio de resposta
        $response['tMed'] = $doc->getElementsByTagName('tMed')->item(0)->nodeValue;
        // data e hora do retorno a operação (opcional)
        $response['dhRetorno'] = !empty($doc->getElementsByTagName('dhRetorno')->item(0)->nodeValue) ?
            date(
                "d/m/Y H:i:s",
                DateTime::st2uts((string) $doc->getElementsByTagName('dhRetorno')->item(0)->nodeValue)
            ) :
            '';
        // data e hora da mensagem (opcional)
        $response['dhRecbto'] = !empty($doc->getElementsByTagName('dhRecbto')->item(0)->nodeValue) ?
            date("d/m/Y H:i:s", DateTime::st2uts((string) $doc->getElementsByTagName('dhRecbto')->item(0)->nodeValue)) :
            '';
        // motivo da resposta (opcional)
        $response['xMotivo'] = !empty($doc->getElementsByTagName('xMotivo')->item(0)->nodeValue) ?
            $doc->getElementsByTagName('xMotivo')->item(0)->nodeValue :
            '';
        // obervaçoes (opcional)
        $response['xObs'] = !empty($doc->getElementsByTagName('xObs')->item(0)->nodeValue) ?
            $doc->getElementsByTagName('xObs')->item(0)->nodeValue :
            '';
        return $returnSoap;
    }//fim isOnline
    
    public function queryRegister()
    {
        
    }//fim queryRegister
    
    public function corretionLetter()
    {
        
    }//fim corretionLetter
    
    public function event()
    {
        
    }//fim event
    
    public function manifest()
    {
        
    }//fim manifest
    
    public function listNFe()
    {
        
    }//fim list
    
    public function download()
    {
        
    }//fim download
    
    public function printDanfe()
    {
        
    }//fim print
    
    public function addB2B()
    {
        
    }//fim addB2B
    
    public function convert()
    {
        
    }//fim convert
    
    public function sendMail()
    {
        
    }//fim sendMail
    
    public function verify()
    {
        
    }//fim verify
    
    public function save()
    {
        
    }//fim save
    
    protected function loadSefaz($sigla, $ambiente)
    {
        $aUrl = array();
        $xml = simplexml_load_file(PATH_ROOT.$this->nfeWs);
        //extrai a variável cUF do lista
        $alias = $this->aliaslist[$sigla];
        //estabelece a expressão xpath de busca
        $xpathExpression = "/WS/UF[sigla='" . $alias . "']/$ambiente";
        //para cada "nó" no xml que atenda aos critérios estabelecidos
        foreach ($xml->xpath($xpathExpression) as $gUF) {
            //para cada "nó filho" retonado
            foreach ($gUF->children() as $child) {
                $u = (string) $child[0];
                $aUrl[$child->getName()]['URL'] = $u;
                // em cada um desses nós pode haver atributos como a identificação
                // do nome do webservice e a sua versão
                foreach ($child->attributes() as $a => $b) {
                    $aUrl[$child->getName()][$a] = (string) $b;
                }
            }
        }
        //verifica se existem outros serviços exclusivos para esse estado
        //isso ocorre normalmente para serviços como a consulta de cadastro
        if ($alias == 'SVAN' || $alias == 'SVRS') {
            $xpathExpression = "/WS/UF[sigla='" . $sigla . "']/$ambiente";
            //para cada "nó" no xml que atenda aos critérios estabelecidos
            foreach ($xml->xpath($xpathExpression) as $gUF) {
                //para cada "nó filho" retonado
                foreach ($gUF->children() as $child) {
                    $u = (string) $child[0];
                    $aUrl[$child->getName()]['URL'] = $u;
                    // em cada um desses nós pode haver atributos como a identificação
                    // do nome do webservice e a sua versão
                    foreach ($child->attributes() as $a => $b) {
                        $aUrl[$child->getName()][$a] = (string) $b;
                    }
                }
            }
        }
        return $aUrl;
    }//fim loadSefaz
}//fim da classe
