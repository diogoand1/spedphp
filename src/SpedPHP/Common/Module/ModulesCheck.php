<?php

namespace SpedPHP\Common\Module;

/**
 * Classe auxiliar para verificação dos modulos instalados no ambiente PHP
 * @category   SpedPHP
 * @package    SpedPHP\Common\Module
 * @copyright  Copyright (c) 2008-2014
 * @license    http://www.gnu.org/licenses/lesser.html LGPL v3
 * @author     Roberto L. Machado <linux.rlm@gamil.com>
 * @link       http://github.com/nfephp-org/spedphp for the canonical source repository
 */

class ModulesCheck
{

    public $Modules = array();

    public function __construct()
    {
        // Stop output of the code and hold in buffer
        ob_start();
        // get loaded modules and their respective settings.
        phpinfo(INFO_MODULES);
        // Get the buffer contents and store in $data variable
        $data = ob_get_contents();
        // Clear buffer
        ob_end_clean();
        // Keep only the items in the <h2>,<th> and <td> tags
        $data = strip_tags($data, '<h2><th><td>');
        // Use regular expressions to filter out needed data
        // Replace everything in the <th> tags and put in <info> tags
        $data = preg_replace('/<th[^>]*>([^<]+)<\/th>/', "<info>\\1</info>", $data);
        // Replace everything in <td> tags and put in <info> tags
        $data = preg_replace('/<td[^>]*>([^<]+)<\/td>/', "<info>\\1</info>", $data);
        // Split the data into an array
        $vTmp = preg_split('/(<h2>[^<]+<\/h2>)/', $data, -1, PREG_SPLIT_DELIM_CAPTURE);
        $vModules = array();
        $count = count($vTmp);
        // Loop through array and add 2 instead of 1
        for ($i=1; $i<$count; $i+=2) {
            // Check to make sure value is a module
            if (preg_match('/<h2>([^<]+)<\/h2>/', $vTmp[$i], $vMat)) {
                // Get the module name
                $moduleName = trim($vMat[1]);
                $vTmp2 = explode("\n", $vTmp[$i+1]);
                foreach ($vTmp2 as $vOne) {
                    // Specify the pattern we created above
                    $vPat = '<info>([^<]+)<\/info>';
                    // Pattern for 2 settings (Local and Master values)
                    $vPat3 = "/$vPat\s*$vPat\s*$vPat/";
                    // Pattern for 1 settings
                    $vPat2 = "/$vPat\s*$vPat/";
                    // This setting has a Local and Master value
                    if (preg_match($vPat3, $vOne, $vMat)) {
                        $vModules[$moduleName][trim($vMat[1])] = array(trim($vMat[2]),trim($vMat[3]));
                    } elseif (preg_match($vPat2, $vOne, $vMat)) {
                         // This setting only has a value
                        $vModules[$moduleName][trim($vMat[1])] = trim($vMat[2]);
                    }
                }

            }
        }
        // Store modules in Modules variable
        $this->Modules = $vModules;
    }

    /**
     * Verifica se o modulo php está carregado
     * @param type $moduleName
     * @return boolean
     */
    public function isLoaded($moduleName)
    {
        return (bool) $this->Modules[$moduleName];
    }

    /**
     * Obtêm o set do modulo
     * @param type $moduleName
     * @param type $setting
     * @return string
     */
    public function getModuleSetting($moduleName, $setting = '')
    {
        // check if module is loaded before continuing
        if ($this->isLoaded($moduleName)==false) {
            // Module not loaded so return error
            return 'Modulo não carregado';
        }
        // You requested an individual setting
        if ($this->Modules[$moduleName][$setting]) {
            return $this->Modules[$moduleName][$setting];
        } elseif (empty($setting)) {
            // List all settings
            return $this->Modules[$moduleName];
        }
        // If setting specified and no value found return error
        return 'Setting not found';
    }
    
    /**
     * Lista todos os modulos instalados
     * @return type
     */
    public function listModules()
    {
        return array_keys($this->Modules);
    }
}
