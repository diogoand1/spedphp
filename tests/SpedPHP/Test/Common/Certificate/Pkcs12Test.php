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
        $dir = './';
        $keyPass = '1234';
        $cnpj='58716523777119';
        $pfxName = 'certificado.pfx';
        $createpemfiles = true;
        $ignorevalidity = false;
        $ignoreowner = false;
        $result = array();
        $pkcs = new Certificate\Pkcs12($dir, $cnpj);
        try {
            $pkcs->loadNewCert($pfxName, $keyPass, $createpemfiles, $ignorevalidity, $ignoreowner);
        } catch (\Exception $e) {
            $result[0] = $e->getMessage();
        }
        $cnpj = '1111111111111';
        $ignorevalidity = true;
        try {
            $pkcs->loadNewCert($pfxName, $keyPass, $createpemfiles, $ignorevalidity, $ignoreowner);
        } catch (\Exception $e) {
            $result[1] = $e->getMessage();
        }
        $ignoreowner = false;
        try {
            $pkcs->loadNewCert($pfxName, $keyPass, $createpemfiles, $ignorevalidity, $ignoreowner);
        } catch (\Exception $e) {
            $result[2] = $e->getMessage();
        }
        $this->assertEquals($result[0], 'Data de validade vencida! [Valido até 23/05/13]');
        $this->assertEquals($result[1], 'O Certificado fornecido pertence a outro CNPJ!!');
        $this->assertEquals($result[2], true);
    }
}
