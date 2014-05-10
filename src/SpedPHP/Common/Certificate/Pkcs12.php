<?php

namespace SpedPHP\Common\Certificate;

/**
 * Classe para tratamento e uso dos certificados digitais modelo A1 (PKCS12)
 * 
 * @category   SpedPHP
 * @package    SpedPHP\Common\Certificate
 * @copyright  Copyright (c) 2008-2014
 * @license    http://www.gnu.org/licenses/lesser.html LGPL v3
 * @author     Roberto L. Machado <linux.rlm@gamil.com>
 * @link       http://github.com/nfephp-org/spedphp for the canonical source repository
 */

use SpedPHP\Common\Certificate\Asn;
use SpedPHP\Common\Components\Xml;
use SpedPHP\Common\Exception;
use \DOMDocument;
use \DOMNode;

class Pkcs12
{
    /**
     * Path para o diretorio onde o arquivo pfx está localizado
     * 
     * @var string 
     */
    public $certsDir;
    /**
     * Nome do arquivo pfx (certificado digital em formato de transporte)
     * 
     * @var string
     */
    public $pfxName;
    /**
     * NUmero do CNPJ do emitente
     * 
     * @var string
     */
    public $cnpj;
    /**
     * String que contêm a chave publica em formato PEM
     * 
     * @var string 
     */
    public $pubKey;
    /**
     * String quem contêm a chave privada em formato PEM
     * 
     * @var string
     */
    public $priKey;
    /**
     * String que conten a combinação da chave publica e privada em formato PEM
     * 
     * @var string
     */
    public $certKey;
    
    /**
     * Path para a chave publica em arquivo
     * 
     * @var string
     */
    public $pubKeyFile;
    /**
     * Path para a chave privada em arquivo
     * 
     * @var string
     */
    public $priKeyFile;
    /**
     * Path para o certificado em arquivo
     * @var string
     */
    public $certKeyFile;
    /**
     * Timestamp da data de validade do certificado
     * 
     * @var float
     */
    public $expireTimestamp;
    /**
     * Mensagem de erro da classe
     * 
     * @var string
     */
    public $error='';

    private $urlDSIG = 'http://www.w3.org/2000/09/xmldsig#';
    private $urlCANONMETH = 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315';
    private $urlSIGMETH = 'http://www.w3.org/2000/09/xmldsig#rsa-sha1';
    private $urlTRANSFMETH1 ='http://www.w3.org/2000/09/xmldsig#enveloped-signature';
    private $urlTRANSFMETH2 = 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315';
    private $urlDIGESTMETH = 'http://www.w3.org/2000/09/xmldsig#sha1';
    
    /**
     * Método de construção da classe
     * 
     * @param string $dir Path para a pasta que contêm os certificados digitais
     * @param string $cnpj CNPJ do emitente, sem  ./-, apenas os numeros
     * @param string $pubKey Chave publica
     * @param string $priKey Chave privada
     * @throws Exception\InvalidArgumentException
     */
    public function __construct($dir, $cnpj, $pubKey = '', $priKey = '')
    {
        $flagCert = false;
        if (!is_dir(trim($dir))) {
            throw new Exception\InvalidArgumentException(
                "Um path válido para os certificados deve ser passado. Diretório [$dir] não foi localizado."
            );
        }
        $this->certsDir = trim($dir);
        if (strlen(trim($cnpj))!= 14) {
            throw new Exception\InvalidArgumentException(
                "Um CNPJ válido deve ser passado e são permitidos apenas números. Valor passado [$cnpj]."
            );
        }
        if ($pubKey != '' && $priKey != '') {
            $this->pubKey = $pubKey;
            $this->priKey = $priKey;
            $this->certKey = $priKey."\r\n".$pubKey;
            $flagCert = true;
        }
        $this->cnpj = trim($cnpj);
        $this->init($flagCert);
    }
    
    /**
     * Método de inicialização da classe irá verificar 
     * os parâmetros, arquivos e validade dos mesmos
     * Em caso de erro o motivo da falha será indicada na parâmetro
     * error da classe, os outros parâmetros serão limpos e os 
     * arquivos inválidos serão removidos da pasta
     * 
     * @param boolean $flagCert indica que as chaves já foram passas como strings
     * @return boolean 
     */
    private function init($flagCert = false)
    {
        if (substr($this->certsDir, -1) !== DIRECTORY_SEPARATOR) {
            $this->certsDir .= DIRECTORY_SEPARATOR;
        }
        //monta o path completo com o nome da chave privada
        $this->priKeyFile = $this->certsDir.$this->cnpj.'_priKEY.pem';
        //monta o path completo com o nome da chave publica
        $this->pubKeyFile =  $this->certsDir.$this->cnpj.'_pubKEY.pem';
        //monta o path completo com o nome do certificado (chave publica e privada) em formato pem
        $this->certKeyFile = $this->certsDir.$this->cnpj.'_certKEY.pem';
        //se as chaves foram passadas na forma de strings então verificar a validade
        if ($flagCert) {
            if (!openssl_x509_read($this->pubKey)) {
                //o dado não é uma chave válida
                $this->removePemFiles();
                $this->leaveParam();
                $this->error = "A chave passada está corrompida ou não é uma chave. Obtenha s chaves corretas!!";
                return false;
            } else {
                //já que o certificado existe, verificar seu prazo de validade
                //o certificado será removido se estiver vencido
                return $this->validCerts($this->pubKey);
            }
        } else {
            //se as chaves não foram passadas em strings, verifica se os certificados existem
            if (is_file($this->priKeyFile) && is_file($this->pubKeyFile) && is_file($this->certKeyFile)) {
                //se as chaves existem deve ser verificado sua validade
                $this->pubKey = file_get_contents($this->pubKeyFile);
                $this->priKey = file_get_contents($this->priKeyFile);
                $this->certKey = file_get_contents($this->certKeyFile);
                if (!openssl_x509_read($this->pubKey)) {
                    //arquivo não pode ser lido como um certificado então deletar
                    $this->removePemFiles();
                    $this->leaveParam();
                    $this->error = "Certificado não instalado. Instale um novo certificado pfx!!";
                    return false;
                } else {
                    //já que o certificado existe, verificar seu prazo de validade
                    return $this->validCerts($this->pubKey);
                }
            } else {
                $this->error = "Certificados não localizados!!";
                return false;
            }
        }
    }//fim init

    /**
     * Apaga os arquivos PEM do diretório
     * Isso deve ser feito quando um novo certificado é carregado
     * ou quando a validade do certificado expirou.
     * 
     */
    private function removePemFiles()
    {
        if (is_file($this->pubKeyFile)) {
            unlink($this->pubKeyFile);
        }
        if (is_file($this->priKeyFile)) {
            unlink($this->priKeyFile);
        }
        if (is_file($this->certKeyFile)) {
            unlink($this->certKeyFile);
        }
    }
    
    /**
     * Limpa os parametros da classe
     * 
     */
    private function leaveParam()
    {
        $this->pfxName='';
        $this->pubKey='';
        $this->priKey='';
        $this->certKey='';
        $this->pubKeyFile='';
        $this->priKeyFile='';
        $this->certKeyFile='';
        $this->expireTimestamp='';
    }
    
    /**
     * Carrega um novo certificado no formato PFX
     * Isso deverá ocorrer a cada atualização do certificado digital, ou seja,
     * pelo menos uma vez por ano, uma vez que a validade do certificado 
     * é anual.
     * Será verificado também se o certificado pertence realmente ao CNPJ
     * indicado na instanciação da classe, se não for um erro irá ocorrer e 
     * o certificado não será convertido para o formato PEM.
     * Em caso de erros, será retornado false e o motivo será indicado no
     * parâmetro error da classe.
     * Os certificados serão armazenados como <CNPJ>-<tipo>.pem  
     * 
     * @param string $pfxName Nome do arquivo PFX que foi salvo na pasta dos certificados
     * @param string $keyPass Senha de acesso ao certificado PFX
     * @param boolean $createFiles se true irá criar os arquivos pem das chaves digitais, caso contrario não
     * @return boolean
     * @throws Exception\InvalidArgumentException
     * @throws Exception\RuntimeException
     */
    public function loadNewCert(
        $pfxName,
        $keyPass = '',
        $createFiles = true,
        $ignorevalidity = false,
        $ignoreowner = false
    ) {
        //monta o caminho completo até o certificado pfx
        $pfxCert = $this->certsDir.$pfxName;
        if (!is_file($pfxCert)) {
            throw new Exception\InvalidArgumentException(
                "O nome do arquivo PFX deve ser passado. Não foi localizado o arquivo [$pfxCert]."
            );
        }
        if ($keyPass == '') {
            throw new Exception\InvalidArgumentException(
                "A senha de acesso para o certificado pfx não pode ser vazia."
            );
        }
        //carrega o certificado em um string
        $pfxContent = file_get_contents($pfxCert);
        //carrega os certificados e chaves para um array denominado $x509certdata
        $x509certdata = array();
        if (!openssl_pkcs12_read($pfxContent, $x509certdata, $keyPass)) {
            throw new Exception\RuntimeException(
                "O certificado não pode ser lido!! Senha errada ou arquivo corrompido ou formato inválido!!"
            );
        }
        if (!$ignorevalidity) {
            //verifica sua data de validade
            if (!$this->validCerts($x509certdata['cert'])) {
                throw new Exception\RuntimeException($this->error);
            }
        }
        if (!$ignoreowner) {
            $cnpjCert = Asn::getCNPJCert($x509certdata['cert']);
            if ($this->cnpj != $cnpjCert) {
                throw new Exception\InvalidArgumentException(
                    "O Certificado fornecido pertence a outro CNPJ!!"
                );
            }
        }
        //monta o path completo com o nome da chave privada
        $this->priKeyFile = $this->certsDir.$this->cnpj.'_priKEY.pem';
        //monta o path completo com o nome da chave publica
        $this->pubKeyFile =  $this->certsDir.$this->cnpj.'_pubKEY.pem';
        //monta o path completo com o nome do certificado (chave publica e privada) em formato pem
        $this->certKeyFile = $this->certsDir.$this->cnpj.'_certKEY.pem';
        $this->removePemFiles();
        if ($createFiles) {
            //recriar os arquivos pem com o arquivo pfx
            if (!file_put_contents($this->priKeyFile, $x509certdata['pkey'])) {
                throw new Exception\RuntimeException(
                    "Falha de permissão de escrita na pasta dos certificados!!"
                );
            }
            file_put_contents($this->pubKeyFile, $x509certdata['cert']);
            file_put_contents($this->certKeyFile, $x509certdata['pkey']."\r\n".$x509certdata['cert']);
        }
        $this->pubKey=$x509certdata['cert'];
        $this->priKey=$x509certdata['pkey'];
        $this->certKey=$x509certdata['pkey']."\r\n".$x509certdata['cert'];
        return true;
    }
    
    /**
     * Método que provê a assinatura do xml conforme padrão SEFAZ
     * 
     * @param string $docxml Path completo para o xml ou o próprio xml em uma string
     * @param string $tagid TAG a ser assinada
     * @return mixed false em caso de erro ou uma string com o conteudo do xml já assinado
     * @throws Exception\InvalidArgumentException
     * @throws Exception\RuntimeException
     */
    public function signXML($docxml, $tagid = '')
    {
        if (is_file($docxml)) {
            $xml = file_get_contents($docxml);
        } else {
            $xml = $docxml;
        }
        $order = array("\r\n", "\n", "\r", "\t");
        $xml = str_replace($order, '', $xml);
        $xmlVal = new Xml\Validate();
        if (!$xmlVal->validXML($xml)) {
            return false;
        }
        if ($tagid == '') {
            $msg = "A tag a ser assinada deve ser indicada.";
            throw new Exception\InvalidArgumentException($msg);
        }
        if ($this->pubKey == '' || $this->priKey == '') {
            $msg = "As chaves não estão disponíveis.";
            throw new Exception\InvalidArgumentException($msg);
        }
        $pkeyid = openssl_get_privatekey($this->priKey);
        // limpeza do xml com a retirada dos CR, LF e TAB
        
        $xmldoc = new \DOMDocument('1.0', 'utf-8');// carrega o documento no DOM
        $xmldoc->preserveWhiteSpace = false; //elimina espaços em branco
        $xmldoc->formatOutput = false;
        $xmldoc->loadXML($xml, LIBXML_NOBLANKS | LIBXML_NOEMPTYTAG);
        $root = $xmldoc->documentElement;
        //extrair a tag com os dados a serem assinados
        $node = $xmldoc->getElementsByTagName($tagid)->item(0);
        if (!isset($node)) {
            throw new Exception\RuntimeException(
                "A tag < $tagid > não existe no XML!!"
            );
        }
        $idNfe = trim($node->getAttribute("Id"));
        //extrai os dados da tag para uma string
        $dados = $node->C14N(false, false, null, null);
        //calcular o hash dos dados
        $hashValue = hash('sha1', $dados, true);
        $digValue = base64_encode($hashValue);
        $signatureNode = $xmldoc->createElementNS($this->urlDSIG, 'Signature');
        $root->appendChild($signatureNode);
        $signedInfoNode = $xmldoc->createElement('SignedInfo');
        $signatureNode->appendChild($signedInfoNode);
        $newNode = $xmldoc->createElement('CanonicalizationMethod');
        $signedInfoNode->appendChild($newNode);
        $newNode->setAttribute('Algorithm', $this->urlCANONMETH);
        $newNode1 = $xmldoc->createElement('SignatureMethod');
        $signedInfoNode->appendChild($newNode1);
        $newNode1->setAttribute('Algorithm', $this->urlSIGMETH);
        $referenceNode = $xmldoc->createElement('Reference');
        $signedInfoNode->appendChild($referenceNode);
        $referenceNode->setAttribute('URI', '#'.$idNfe);
        $transformsNode = $xmldoc->createElement('Transforms');
        $referenceNode->appendChild($transformsNode);
        $newNode2 = $xmldoc->createElement('Transform');
        $transformsNode->appendChild($newNode2);
        $newNode2->setAttribute('Algorithm', $this->urlTRANSFMETH1);
        $newNode3 = $xmldoc->createElement('Transform');
        $transformsNode->appendChild($newNode3);
        $newNode3->setAttribute('Algorithm', $this->urlTRANSFMETH2);
        $newNode4 = $xmldoc->createElement('DigestMethod');
        $referenceNode->appendChild($newNode4);
        $newNode4->setAttribute('Algorithm', $this->urlDIGESTMETH);
        $newNode5 = $xmldoc->createElement('DigestValue', $digValue);
        $referenceNode->appendChild($newNode5);
        // extrai os dados a serem assinados para uma string
        $dados1 = $signedInfoNode->C14N(false, false, null, null);
        $signature = '';
        openssl_sign($dados1, $signature, $pkeyid);
        $signatureValue = base64_encode($signature);
        $newNode6 = $xmldoc->createElement('SignatureValue', $signatureValue);
        $signatureNode->appendChild($newNode6);
        $keyInfoNode = $xmldoc->createElement('KeyInfo');
        $signatureNode->appendChild($keyInfoNode);
        $x509DataNode = $xmldoc->createElement('X509Data');
        $keyInfoNode->appendChild($x509DataNode);
        $cert = $this->cleanCerts();
        $newNode7 = $xmldoc->createElement('X509Certificate', $cert);
        $x509DataNode->appendChild($newNode7);
        $xmlResp = $xmldoc->saveXML();
        openssl_free_key($pkeyid);
        //retorna o documento assinado
        return $xmlResp;
    }
    
    /**
     * Verifica a validade da assinatura digital contida no xml
     *  
     * @param string $xml path para o xml ou o conteudo do mesmo em uma string
     * @param string $tag tag que foi assinada no documento xml
     * @return boolean
     * @throws Exception\InvalidArgumentException
     * @throws Exception\RuntimeException
     */
    public function verifySignature($xml = '', $tag = '')
    {
        if ($xml=='') {
            $msg = "O parâmetro xml está vazio.";
            throw new Exception\InvalidArgumentException($msg);
        }
        if ($tag=='') {
            $msg = "O parâmetro tag está vazio.";
            throw new Exception\InvalidArgumentException($msg);
        }
        $dom = new \DOMDocument('1.0', 'utf-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        if (!is_file($xml)) {
            $dom->loadXML($xml, LIBXML_NOBLANKS | LIBXML_NOEMPTYTAG);
        } else {
            $dom->load($xml, LIBXML_NOBLANKS | LIBXML_NOEMPTYTAG);
        }
        $tagBase = $dom->getElementsByTagName($tag)->item(0);
        // validar digest value
        $tagInf = $tagBase->C14N(false, false, null, null);
        $hashValue = hash('sha1', $tagInf, true);
        $digestCalculado = base64_encode($hashValue);
        $digestInformado = $dom->getElementsByTagName('DigestValue')->item(0)->nodeValue;
        if ($digestCalculado != $digestInformado) {
            $msg = "O conteúdo do XML não confere com o Digest Value.\n
                Digest calculado [{$digestCalculado}], informado no XML [{$digestInformado}].\n
                O arquivo pode estar corrompido ou ter sido adulterado.";
            throw new Exception\RuntimeException($msg);
        }
        // Remontando o certificado
        $x509Certificate = $dom->getElementsByTagName('X509Certificate')->item(0)->nodeValue;
        $x509Certificate =  "-----BEGIN CERTIFICATE-----\n".
        $this->splitLines($x509Certificate)."\n-----END CERTIFICATE-----\n";
        $pubKey = openssl_pkey_get_public($x509Certificate);
        if ($pubKey === false) {
            $msg = "Ocorreram problemas ao remontar a chave pública. Certificado incorreto ou corrompido!!";
            throw new Exception\RuntimeException($msg);
        }
        // remontando conteudo que foi assinado
        $signContent = $dom->getElementsByTagName('SignedInfo')->item(0)->C14N(false, false, null, null);
        // validando assinatura do conteudo
        $signContentXML = $dom->getElementsByTagName('SignatureValue')->item(0)->nodeValue;
        $signContentXML1 = \base64_decode(str_replace(array("\r", "\n"), '', $signContentXML));
        $resp = \openssl_verify($signContent, $signContentXML1, $pubKey);
        if ($resp != 1) {
            $msg = "Problema ({$resp}) ao verificar a assinatura do digital!!";
            throw new Exception\RuntimeException($msg);
        }
        return true;
    }
    
    /**
     * Verifica a data de validade do certificado digital
     * e compara com a data de hoje.
     * Caso o certificado tenha expirado o mesmo será removido das
     * pastas e o método irá retornar false.
     * 
     * @param string $pubKey chave publica
     * @return boolean
     */
    protected function validCerts($pubKey)
    {
        $data = \openssl_x509_read($pubKey);
        $certData = \openssl_x509_parse($data);
        // reformata a data de validade;
        $ano = substr($certData['validTo'], 0, 2);
        $mes = substr($certData['validTo'], 2, 2);
        $dia = substr($certData['validTo'], 4, 2);
        //obtem o timestamp da data de validade do certificado
        $dValid = \gmmktime(0, 0, 0, $mes, $dia, $ano);
        // obtem o timestamp da data de hoje
        $dHoje = \gmmktime(0, 0, 0, date("m"), date("d"), date("Y"));
        // compara a data de validade com a data atual
        $this->expireTimestamp = $dValid;
        if ($dHoje > $dValid) {
            $this->removePemFiles();
            $this->leaveParam();
            $msg = "Data de validade vencida! [Valido até $dia/$mes/$ano]";
            $this->error = $msg;
            return false;
        }
        return true;
    }
    
    /**
     * Remove a informação de inicio e fim do certificado
     * contido no formato PEM, deixando o certificado (chave publica) pronta para ser
     * anexada ao xml da NFe
     * 
     * @return string
     */
    protected function cleanCerts()
    {
        //inicializa variavel
        $data = '';
        //carregar a chave publica
        $pubKey = $this->pubKey;
        //carrega o certificado em um array usando o LF como referencia
        $arCert = explode("\n", $pubKey);
        foreach ($arCert as $curData) {
            //remove a tag de inicio e fim do certificado
            if (strncmp($curData, '-----BEGIN CERTIFICATE', 22) != 0 &&
                    strncmp($curData, '-----END CERTIFICATE', 20) != 0 ) {
                //carrega o resultado numa string
                $data .= trim($curData);
            }
        }
        return $data;
    }
    
    /**
     * Divide a string do certificado publico em linhas
     * com 76 caracteres (padrão original)
     * 
     * @param string $cntIn certificado
     * @return string certificado reformatado 
     */
    public static function splitLines($cntIn = '')
    {
        if ($cntIn != '') {
            $cnt = rtrim(chunk_split(str_replace(array("\r", "\n"), '', $cntIn), 76, "\n"));
        } else {
            $cnt = $cntIn;
        }
        return $cnt;
    }
}
