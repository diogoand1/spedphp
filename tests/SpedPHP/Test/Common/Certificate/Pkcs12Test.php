<?php
namespace SpedPHP\Test\Common\Certificate;

use SpedPHP\Common\Certificate\Pkcs12;

/**
 * @category   SpedPHP
 * @package    SpedPHP\Test\Common\Certificate
 * @copyright  Copyright (c) 2008-2014
 * @license   http://www.gnu.org/licenses/lesser.html LGPL v3
 * @author     Roberto L. Machado <linux.rlm@gamil.com>
 * @link      http://github.com/nfephp-org/spedphp for the canonical source repository
 */

class Pkcs12Test extends \PHPUnit_Framework_TestCase
{
    
    public function testloadNewCertCert()
    {
        $dir = dirname(__FILE__);
        $keyPass = '1234';
        $cnpj='58716523777119';
        $pfxName = 'certificado.pfx';
        $createpemfiles = false;
        $ignorevalidity = false;
        $ignoreowner = false;
        $result = array();
        
        try {
            $pkcs = new Pkcs12($dir, $cnpj);
            $pkcs->loadNewCert($pfxName, $keyPass, $createpemfiles, $ignorevalidity, $ignoreowner);
        } catch (\Exception $e) {
            $result[0] = $e->getMessage();
        }
        $pkcs = null;
        
        $cnpj = '12345678901234';
        $ignorevalidity = true;
        try {
            $pkcs = new Pkcs12($dir, $cnpj);
            $pkcs->loadNewCert($pfxName, $keyPass, $createpemfiles, $ignorevalidity, $ignoreowner);
        } catch (\Exception $e) {
            $result[1] = $e->getMessage();
        }
        $pkcs = null;
        
        $ignoreowner = true;
        try {
            $pkcs = new Pkcs12($dir, $cnpj);
            $result[2] = $pkcs->loadNewCert($pfxName, $keyPass, $createpemfiles, $ignorevalidity, $ignoreowner);
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
        
        $this->assertEquals($result[0], 'Data de validade vencida! [Valido até 23/05/13]');
        $this->assertEquals($result[1], 'O Certificado fornecido pertence a outro CNPJ!!');
        $this->assertEquals($result[2], true);
    }
    
    public function testsignXML()
    {
        $dir = dirname(__FILE__);
        $cnpj = '12345678901234';
        $keyPass = '1234';
        $pfxName = 'certificado.pfx';
        $createpemfiles = false;
        $ignorevalidity = true;
        $ignoreowner = true;
        $xmlfile = $dir.DIRECTORY_SEPARATOR.'nfe.xml';
        $xmlfilesigned = $dir.DIRECTORY_SEPARATOR.'nfe_signed.xml';
        $xml = file_get_contents($xmlfile);
        $xmlsigned = file_get_contents($xmlfilesigned);
        
        $pkcs = new Pkcs12($dir, $cnpj);
        $pkcs->loadNewCert($pfxName, $keyPass, $createpemfiles, $ignorevalidity, $ignoreowner);
        $xmlresp = $pkcs->signXML($xml, 'infNFe');
        
        $this->assertEquals($xmlresp, $xmlsigned);
    }
    
    public function testverifySignature()
    {
        $dir = dirname(__FILE__);
        $cnpj = '12345678901234';
        $xmlfilesigned = $dir.DIRECTORY_SEPARATOR.'nfe_signed.xml';
        $xmlsigned = file_get_contents($xmlfilesigned);
        $pkcs = new Pkcs12($dir, $cnpj);
        $resp = $pkcs->verifySignature($xmlsigned, 'infNFe');
        
        $this->assertEquals($resp, true);
    }
}
