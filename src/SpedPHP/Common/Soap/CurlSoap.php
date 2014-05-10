<?php

namespace SpedPHP\Common\Soap;

/**
 * Classe auxiliar para envio das mensagens SOAP usando cURL
 * @category   SpedPHP
 * @package    SpedPHP\Common\Soap
 * @copyright  Copyright (c) 2008-2014
 * @license    http://www.gnu.org/licenses/lesser.html LGPL v3
 * @author     Roberto L. Machado <linux.rlm@gamil.com>
 * @link       http://github.com/nfephp-org/spedphp for the canonical source repository
 */

class CurlSoap
{
    /**
     * soapDebug
     * @var string
     */
    public $soapDebug = '';
    /**
     * error
     * @var string
     */
    public $error = '';
    /**
     * soapTimeout
     * @var integer
     */
    public $soapTimeout = 10;
    
    private $errorCurl = '';
    private $infoCurl = array();
    private $pubKEY = '';
    private $priKEY = '';
    private $proxyIP = '';
    private $proxyPORT = '';
    private $proxyUSER = '';
    private $proxyPASS = '';
    private $faultCode = array(
            '100'=>'Continue',
            '101'=>'Switching Protocols',
            '200'=>'OK',
            '201'=>'Created',
            '202'=>'Accepted',
            '203'=>'Non-Authoritative Information',
            '204'=>'No Content',
            '205'=>'Reset Content',
            '206'=>'Partial Content',
            '300'=>'Multiple Choices',
            '301'=>'Moved Permanently',
            '302'=>'Found',
            '303'=>'See Other',
            '304'=>'Not Modified',
            '305'=>'Use Proxy',
            '306'=>'(Unused)',
            '307'=>'Temporary Redirect',
            '400'=>'Bad Request',
            '401'=>'Unauthorized',
            '402'=>'Payment Required',
            '403'=>'Forbidden',
            '404'=>'Not Found',
            '405'=>'Method Not Allowed',
            '406'=>'Not Acceptable',
            '407'=>'Proxy Authentication Required',
            '408'=>'Request Timeout',
            '409'=>'Conflict',
            '410'=>'Gone',
            '411'=>'Length Required',
            '412'=>'Precondition Failed',
            '413'=>'Request Entity Too Large',
            '414'=>'Request-URI Too Long',
            '415'=>'Unsupported Media Type',
            '416'=>'Requested Range Not Satisfiable',
            '417'=>'Expectation Failed',
            '500'=>'Internal Server Error',
            '501'=>'Not Implemented',
            '502'=>'Bad Gateway',
            '503'=>'Service Unavailable',
            '504'=>'Gateway Timeout',
            '505'=>'HTTP Version Not Supported');
    
    /**
     * __construct
     * 
     * @param string $privateKey path para a chave privada
     * @param string $publicKey path para a chave publica
     * @param string $timeout tempo de espera da resposta do webservice
     */
    public function __construct($privateKey, $publicKey, $timeout = 10)
    {
        $this->priKEY = $privateKey;
        $this->pubKEY = $publicKey;
        $this->soapTimeout = $timeout;
    }//fim __construct
    
    /**
     * Seta o uso do proxy
     * 
     * @param string $ipNumber numero IP do proxy server
     * @param string $port numero da porta usada pelo proxy
     * @param string $user nome do usuário do proxy
     * @param string $pass senha de acesso ao proxy
     * @return boolean
     */
    public function setProxy($ipNumber, $port, $user = '', $pass = '')
    {
        $this->proxyIP = $ipNumber;
        $this->proxyPORT = $port;
        $this->proxyUSER = $user;
        $this->proxyPASS = $pass;
    }//fim setProxy
    
    /**
     * Envia mensagem ao webservice
     * 
     * @param string $urlsevice
     * @param string $namespace
     * @param string $header
     * @param string $body
     * @param string $method
     * @return boolean|string
     */
    public function send($urlservice, $namespace, $header, $body, $method)
    {
        //monta a mensagem ao webservice
        $data = '<?xml version="1.0" encoding="utf-8"?>'.'<soap12:Envelope ';
        $data .= 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" ';
        $data .= 'xmlns:xsd="http://www.w3.org/2001/XMLSchema" ';
        $data .= 'xmlns:soap12="http://www.w3.org/2003/05/soap-envelope">';
        $data .= '<soap12:Header>'.$header.'</soap12:Header>';
        $data .= '<soap12:Body>'.$body.'</soap12:Body>';
        $data .= '</soap12:Envelope>';
        $data = $this->limpaMsg($data);
        //tamanho da mensagem
        $tamanho = strlen($data);
        //estabelecimento dos parametros da mensagem
        $parametros = array(
            'Content-Type: application/soap+xml;charset=utf-8;action="'.$namespace."/".$method.'"',
            'SOAPAction: "'.$method.'"',
            "Content-length: $tamanho");
        //solicita comunicação via cURL
        $xml = $this->commCurl($urlservice, $data, $parametros);
        //obtem o tamanho do xml
        $num = strlen($xml);
        //localiza a primeira marca de tag
        $xPos = stripos($xml, "<");
        //se não exixtir não é um xml
        if ($xPos !== false) {
            $xml = substr($xml, $xPos, $num-$xPos);
        } else {
            $xml = '';
        }
        //testa se um xml foi retornado
        if ($xml == '' || $xPos === false) {
            //não houve retorno
            $this->error = $this->errorCurl . $this->infoCurl['http_code'].
                    $this->faultCode[$this->infoCurl['http_code']];
            return false;
        } else {
            //houve retorno mas ainda pode ser uma mensagem de erro do webservice
            if ($this->infoCurl['http_code'] > 200) {
                $this->error = $this->infoCurl['http_code'].
                        $this->faultCode[$this->infoCurl['http_code']];
                return false;
            }
        }
        return $xml;
    } //fim send

    /**
     * Baixa o arquivo wsdl do webservice
     * 
     * @param string $urlsefaz
     * @return boolean|string
     */
    public function getWsdl($urlservice)
    {
        $resposta = $this->commCurl($urlservice);
        //verifica se foi retornado o wsdl
        $nPos = strpos($resposta, '<wsdl:def');
        if ($nPos === false) {
            $nPos = strpos($resposta, '<definit');
        }
        if ($nPos === false) {
            //não retornou um wsdl
            return false;
        }
        $wsdl = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n".trim(substr($resposta, $nPos));
        return $wsdl;
    }//fim getWsdl

    /**
     * Realiza da comunicação via cURL
     * 
     * @param string $url
     * @param string $data
     * @param string $parametros
     * @return string
     */
    private function commCurl($url, $data = '', $parametros = array())
    {
        //incializa cURL
        $oCurl = curl_init();
        //setting da seção soap
        if ($this->proxyIP != '') {
            curl_setopt($oCurl, CURLOPT_HTTPPROXYTUNNEL, 1);
            curl_setopt($oCurl, CURLOPT_PROXYTYPE, "CURLPROXY_HTTP");
            curl_setopt($oCurl, CURLOPT_PROXY, $this->proxyIP.':'.$this->proxyPORT);
            if ($this->proxyPASS != '') {
                curl_setopt($oCurl, CURLOPT_PROXYUSERPWD, $this->proxyUSER.':'.$this->proxyPASS);
                curl_setopt($oCurl, CURLOPT_PROXYAUTH, "CURLAUTH_BASIC");
            } //fim if senha proxy
        }//fim if aProxy
        curl_setopt($oCurl, CURLOPT_CONNECTTIMEOUT, $this->soapTimeout);
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_PORT, 443);
        curl_setopt($oCurl, CURLOPT_VERBOSE, 1);
        curl_setopt($oCurl, CURLOPT_HEADER, 1);
        curl_setopt($oCurl, CURLOPT_SSLVERSION, 3);
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($oCurl, CURLOPT_SSLCERT, $this->pubKEY);
        curl_setopt($oCurl, CURLOPT_SSLKEY, $this->priKEY);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        if ($data != '') {
            curl_setopt($oCurl, CURLOPT_POST, 1);
            curl_setopt($oCurl, CURLOPT_POSTFIELDS, $data);
        }
        if (!empty($parametros)) {
            curl_setopt($oCurl, CURLOPT_HTTPHEADER, $parametros);
        }
        //inicia a conexão
        $resposta = curl_exec($oCurl);
        //obtem as informações da conexão
        $info = curl_getinfo($oCurl);
        $this->infoCurl = $info;
        $this->errorCurl = curl_error($oCurl);
        //fecha a conexão
        curl_close($oCurl);
        //coloca as informações em uma variável
        $txtInfo ="";
        $txtInfo .= "URL=$info[url]\n";
        $txtInfo .= "Content type=$info[content_type]\n";
        $txtInfo .= "Http Code=$info[http_code]\n";
        $txtInfo .= "Header Size=$info[header_size]\n";
        $txtInfo .= "Request Size=$info[request_size]\n";
        $txtInfo .= "Filetime=$info[filetime]\n";
        $txtInfo .= "SSL Verify Result=$info[ssl_verify_result]\n";
        $txtInfo .= "Redirect Count=$info[redirect_count]\n";
        $txtInfo .= "Total Time=$info[total_time]\n";
        $txtInfo .= "Namelookup=$info[namelookup_time]\n";
        $txtInfo .= "Connect Time=$info[connect_time]\n";
        $txtInfo .= "Pretransfer Time=$info[pretransfer_time]\n";
        $txtInfo .= "Size Upload=$info[size_upload]\n";
        $txtInfo .= "Size Download=$info[size_download]\n";
        $txtInfo .= "Speed Download=$info[speed_download]\n";
        $txtInfo .= "Speed Upload=$info[speed_upload]\n";
        $txtInfo .= "Download Content Length=$info[download_content_length]\n";
        $txtInfo .= "Upload Content Length=$info[upload_content_length]\n";
        $txtInfo .= "Start Transfer Time=$info[starttransfer_time]\n";
        $txtInfo .= "Redirect Time=$info[redirect_time]\n";
        $txtInfo .= "Certinfo=$info[certinfo]\n";
        //carrega a variavel debug
        $this->soapDebug = $data."\n\n".$txtInfo."\n".$resposta;
        //retorna
        return $resposta;
    }//fim sendCurl
    
    /**
     * limpaMsg
     * 
     * @param string $msg
     * @return string
     */
    private function limpaMsg($msg)
    {
        $nmsg = str_replace(array("\n","\r","\t"), array('','',''), $msg);
        $nnmsg = str_replace('> ', '>', $nmsg);
        if (strpos($nnmsg, '> ')) {
            $this->limpaMsg((string) $nnmsg);
        }
        return $nnmsg;
    }//Fim limpaMsg
}//fim da classe CurlSoap
