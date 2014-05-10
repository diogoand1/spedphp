<?php

namespace Spedphp\Common\Soap;

use \SoapClient;

/**
 * Classe auxiliar para o envio de mesagens SOAP usando o SOAP nativo do PHP
 * @category   SpedPHP
 * @package    SpedPHP\Common\Soap
 * @copyright  Copyright (c) 2008-2014
 * @license    http://www.gnu.org/licenses/lesser.html LGPL v3
 * @author     Roberto L. Machado <linux.rlm@gamil.com>
 * @link       http://github.com/nfephp-org/spedphp for the canonical source repository
 */

/**
 * 
 * Classe complementar
 * necessária para a comunicação SOAP 1.2
 * Remove algumas tags para adequar a comunicação
 * ao padrão "esquisito" utilizado pelas SEFAZ
 *
 * @name CorrectSoapClient
 *
 */
class CorrectedSoapClient extends \SoapClient
{
    public function __construct($sefazURL, $options)
    {
        parent::__construct($sefazURL, $options);
    }
    
    public function __doRequest($request, $location, $action, $version, $oneWay = 0)
    {
        $aFind = array(":ns1","ns1:","\n","\r");
        $aReplace = array("","","","");
        $newrequest = str_replace($aFind, $aReplace, $request);
        return parent::__doRequest($newrequest, $location, $action, $version, $oneWay);
    }
}//fim da classe
