<?php

namespace Spedphp\Common\Soap;

use SpedPHP\Common\Soap;
use SpedPHP\Common\Exception;
use LSS\XML2Array;

/**
 * @category   SpedPHP
 * @package    SpedPHP\Common\Soap
 * @copyright  Copyright (c) 2008-2014
 * @license    http://www.gnu.org/licenses/lesser.html LGPL v3
 * @author     Roberto L. Machado <linux.rlm@gamil.com>
 * @link       http://github.com/nfephp-org/spedphp for the canonical source repository
 */

class Wsdl
{
    public $soapDebug='';
    public $error='';
    
    /**
     * updateWsdl
     * Atualiza todos os arquivos wsdl de todos os webservices
     * cadastrados no arquivo wsFile
     * 
     * @param string $wsdlDir Path para o diretorio dos arquivos WSDL
     * @param type $wsFile Path para o arquivo de cadastramento dos webservices
     * @param type $privateKey Path para o arquivo da chave privada
     * @param type $publicKey Path para o arquivo da chave publica
     * @return boolean|string False se houve algum erro no download, e lista dos wsdl baixados
     */
    public function updateWsdl($wsdlDir, $wsFile, $privateKey, $publicKey)
    {
        $contagem = 1;
        $msg = '';
        //pega o conteúdo do xml com os endereços dos webservices
        $xml = file_get_contents($wsFile);
        //converte o xml em array
        $aWS = XML2Array::createArray($xml);
        //para cada UF
        foreach ($aWS['WS']['UF'] as $uf) {
            $sigla = $uf['sigla'];
            $aAmb = array('homologacao','producao');
            //para cada ambiente
            foreach ($aAmb as $amb) {
                $aService = $uf[$amb];
                if (isset($aService)) {
                    foreach ($aService as $service) {
                        $url=$service['@value'];
                        $metodo=$service['@attributes']['method'];
                        if ($url != '') {
                            $urlsefaz = $url.'?wsdl';
                            $fileName = $wsdlDir.DIRECTORY_SEPARATOR.$amb.DIRECTORY_SEPARATOR.
                                    $sigla.'_'.$metodo.'.asmx';
                            if ($wsdl = $this->downLoadWsdl($urlsefaz, $privateKey, $publicKey)) {
                                file_put_contents($fileName, $wsdl);
                                chmod($fileName, 755);
                                $msg .= "[$contagem] - $urlsefaz\n";
                                $contagem++;
                            } else {
                                $this->error .= "Falha ao baixar $urlsefaz !! $this->soapDebug\n";
                            }//fim
                        }//fim if url
                        //colocar um intervalo para não ter as solicitações negadas
                        sleep(60);
                    }//fim foreach service
                }//fim if
            } //fim foreach amb
        }//fim foreach ws
        if ($this->error != '') {
            return false;
        }
        return $msg;
    }//fim updateWsdl
    
    /**
     * downloadWsdl
     * Baixa os arquivos wsdl necessários para a comunicação com 
     * SOAP nativo
     * @param string $url
     * @param string $privateKey
     * @param string $publicKey
     * @return type
     */
    public function downLoadWsdl($url, $privateKey, $publicKey)
    {
        $soap = new Soap\CurlSoap($privateKey, $publicKey);
        $resposta = $soap->getWsdl($url);
        if (!$resposta) {
            $this->soapDebug = $soap->soapDebug;
            return false;
        }
        return $resposta;
    }//fim downLoadWsdl
}//fim WSDL
