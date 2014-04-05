<?php
/*
 * NFePHP (http://www.nfephp.org/)
 *
 * @link      http://github.com/nfephp-org/nfephp for the canonical source repository
 * @copyright Copyright (c) 2008-2013 NFePHP (http://www.nfephp.org)
 * @license   http://www.gnu.org/licenses/lesser.html LGPL v3
 * @license   http://www.gnu.org/licenses/gpl.html GNU/GPL v.3
 * @package   NFePHP
 */

namespace SpedPHP\Common\Util;

use SpedPHP\Common\Exception;

/**
 * 
 */
class Utils
{

    /**
     * Método para obter todo o conteúdo de um diretorio, e
     * que atendam ao critério indicado.
     * 
     * @param string $dir Diretorio a ser pesquisado
     * @param string $fileMatch critério de seleção pode ser usados coringas como *-nfe.xml
     * @param boolean $retpath se true retorna o path completo dos arquivos se false so retorna o nome dos arquivos
     * @return mixed Matriz com os nome dos arquivos que atendem ao critério estabelecido ou false
     */
    public function dirListFiles($dir = '', $fileMatch = '', $retPath = false)
    {
        if (trim($fileMatch) != '' && trim($dir) != '') {
            //passar o padrão para minúsculas
            $fileMatch = strtolower($fileMatch);
            //guarda o diretorio atual
            $oldDir = getcwd().DIRECTORY_SEPARATOR;
            //verifica se o parametro $dir define um diretorio real
            if (is_dir($dir)) {
                //mude para o novo diretorio
                chdir($dir);
                //pegue o diretorio
                $diretorio = getcwd().DIRECTORY_SEPARATOR;
                if (strtolower($dir) != strtolower($diretorio)) {
                    $msg = "Falha! sem permissão de leitura no diretorio escolhido.";
                    throw new Exception\NfephpException($msg);
                }
                $aName = $this->dirGetFiles($diretorio, $fileMatch, $retPath);
                //volte para o diretorio anterior
                chdir($oldDir);
            }//endif do teste se é um diretorio
        }//endif
        sort($aName);
        return $aName;
    } //fim listDir

    private function dirGetFiles($diretorio, $fileMatch, $retPath = false)
    {
        //abra o diretório
        $ponteiro  = opendir($diretorio);
        // monta os vetores com os itens encontrados na pasta
        while (false !== ($file = readdir($ponteiro))) {
            //procure se não for diretorio
            if ($file != "." && $file != "..") {
                if (!is_dir($file)) {
                    $tfile = strtolower($file);
                    //é um arquivo então
                    //verifique se combina com o $fileMatch
                    if (fnmatch($fileMatch, $tfile)) {
                        if ($retPath) {
                            $aName[] = $diretorio.$file;
                        } else {
                            $aName[] = $file;
                        }
                    }
                } //endif é diretorio
            } //endif é  . ou ..
        }//endwhile
        closedir($ponteiro);
        return $aName;
    }
  
    /**
     * splitLines
     * Divide a string do certificado publico em linhas com 76 caracteres (padrão original)
     * 
     * @name splitLines
     * @param string $cnt certificado
     * @return string certificado reformatado 
     */
    public static function splitLines($cnt = '')
    {
        if ($cnt != '') {
            $cnt = rtrim(chunk_split(str_replace(array("\r", "\n"), '', $cnt), 76, "\n"));
        }
        return $cnt;
    }//fim splitLines
    
    
    /**
     * cleanString
     * Remove todos dos caracteres especiais, simbolos e acentos
     *  
     * @name cleanString
     * @return  string Texto sem caractere especiais
     */
    public static function cleanString($texto)
    {
        $aFind = array('&','á','à','ã','â','é','ê','í','ó','ô','õ','ú','ü','ç','Á',
            'À','Ã','Â','É','Ê','Í','Ó','Ô','Õ','Ú','Ü','Ç');
        $aSubs = array('e','a','a','a','a','e','e','i','o','o','o','u','u','c','A',
            'A','A','A','E','E','I','O','O','O','U','U','C');
        $novoTexto = str_replace($aFind, $aSubs, $texto);
        $novoTexto = preg_replace("/[^a-zA-Z0-9 @,-.;:\/]/", "", $novoTexto);
        return $novoTexto;
    }//fim cleanString
}
