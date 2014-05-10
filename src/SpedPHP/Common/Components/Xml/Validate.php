<?php

namespace SpedPHP\Common\Components\Xml;

/**
 * @category   SpedPHP
 * @package    SpedPHP\Common\Components\Xml
 * @copyright  Copyright (c) 2008-2014
 * @license    http://www.gnu.org/licenses/lesser.html LGPL v3
 * @author     Roberto L. Machado <linux.rlm@gamil.com>
 * @link       http://github.com/nfephp-org/spedphp for the canonical source repository
 */

use SpedPHP\Common\Exception;

class Validate
{
    /**
     * Contêm os erros retornados da analise do xml
     * 
     * @var array
     */
    public $aError = array();
    
    /**
     * 
     */
    public function __construct()
    {
        // Habilita a manipulaçao de erros da libxml
        libxml_use_internal_errors(true);
        //limpar erros anteriores que possam estar em memória
        libxml_clear_errors();
    }
    
    /**
     * Verifica a validade do xml com base no xsd
     * Esta função pode validar qualquer arquivo xml do sistema de NFe
     * Há um bug no libxml2 para versões anteriores a 2.7.3
     * que causa um falso erro na validação da NFe devido ao
     * uso de uma marcação no arquivo tiposBasico_v1.02.xsd
     * onde se le {0 , } substituir por *
     * A validação não deve ser feita após a inclusão do protocolo !!!
     * Caso seja passado uma NFe ainda não assinada a falta da assinatura será desconsiderada.
     *
     * @name validXML
     * @param string  $xml  string contendo o arquivo xml a ser validado ou seu path
     * @param string  $xsdfile Path completo para o arquivo xsd
     * @param array   $aError Variável passada como referencia irá conter as mensagens de erro se houverem 
     * @return boolean 
     */
    public function validXML($xml = '', $xsdFile = '', &$aError = array())
    {
        try {
            //verifica se foi passado o xml
            if (strlen($xml)==0) {
                $msg = 'Você deve passar o conteudo do xml como parâmetro ou o caminho completo até o arquivo.';
                throw new Exception\InvalidArgumentException($msg);
            }
            // instancia novo objeto DOM
            $dom = new Document('1.0', 'utf-8');
            $dom->preserveWhiteSpace = false; //elimina espaços em branco
            $dom->formatOutput = false;
            // carrega o xml tanto pelo string contento o xml como por um path
            if (is_file($xml)) {
                $dom->load($xml, LIBXML_NOBLANKS | LIBXML_NOEMPTYTAG);
            } else {
                $dom->loadXML($xml, LIBXML_NOBLANKS | LIBXML_NOEMPTYTAG);
            }
            //recupera os erros da libxml
            $errors = \libxml_get_errors();
            if (!empty($errors)) {
                $msg = $this->xmlErros($errors);
                $aError = $this->aError;
                //o dado passado como $docXml não é um xml
                $msg = 'O dado informado não é um XML ou não foi encontrado. '.$msg;
                throw new Exception\RuntimeException($msg);
            }
            //limpa erros anteriores
            libxml_clear_errors();
            if (is_file($xsdFile)) {
                // valida o xml com o xsd
                if (!$dom->schemaValidate($xsdFile)) {
                    // carrega os erros em um array
                    $aIntErrors = libxml_get_errors();
                    $msg = $this->xmlErros($aIntErrors);
                    $aError = $this->aError;
                    throw new Exception\RuntimeException($msg);
                }
            }
        } catch (Exception\RuntimeException $e) {
            $this->aError[] = $e->getMessage();
            return false;
        }
        return true;
    } //fim validXML

    /**
     * Lista os erros em portugues 
     * 
     * @param array $aIntErrors
     * @return string
     */
    private function xmlErros($aIntErrors)
    {
        $msg = '';
        foreach ($aIntErrors as $intError) {
            $en = array("{http://www.portalfiscal.inf.br/nfe}"
                ,"[facet 'pattern']"
                ,"The value"
                ,"is not accepted by the pattern"
                ,"has a length of"
                ,"[facet 'minLength']"
                ,"this underruns the allowed minimum length of"
                ,"[facet 'maxLength']"
                ,"this exceeds the allowed maximum length of"
                ,"Element"
                ,"attribute"
                ,"is not a valid value of the local atomic type"
                ,"is not a valid value of the atomic type"
                ,"Missing child element(s). Expected is"
                ,"The document has no document element"
                ,"[facet 'enumeration']"
                ,"one of"
                ,"failed to load external entity"
                ,"Failed to locate the main schema resource at"
                ,"This element is not expected. Expected is"
                ,"is not an element of the set");
            
            $pt = array(""
                ,"[Erro 'Layout']"
                ,"O valor"
                ,"não é aceito para o padrão."
                ,"tem o tamanho"
                ,"[Erro 'Tam. Min']"
                ,"deve ter o tamanho mínimo de"
                ,"[Erro 'Tam. Max']"
                ,"Tamanho máximo permitido"
                ,"Elemento"
                ,"Atributo"
                ,"não é um valor válido"
                ,"não é um valor válido"
                ,"Elemento filho faltando. Era esperado"
                ,"Falta uma tag no documento"
                ,"[Erro 'Conteúdo']"
                ,"um de"
                ,"falha ao carregar entidade externa"
                ,"Falha ao tentar localizar o schema principal em"
                ,"Este elemento não é esperado. Esperado é"
                ,"não é um dos seguintes possiveis");
                    
            switch ($intError->level) {
                case LIBXML_ERR_WARNING:
                    $this->aError[] = " Atenção $intError->code: " . str_replace($en, $pt, $intError->message);
                    break;
                case LIBXML_ERR_ERROR:
                    $this->aError[] = " Erro $intError->code: " . str_replace($en, $pt, $intError->message);
                    break;
                case LIBXML_ERR_FATAL:
                    $this->aError[] = " Erro Fatal $intError->code: " . str_replace($en, $pt, $intError->message);
                    break;
            }
            $msg .= str_replace($en, $pt, $intError->message);
        }
        return $msg;
    }
}
