<?php
/**
 * Concrete class for generating debug dumps related to the output source.
 *
 * @category   Zuora
 * @package    Zuora_Debug
 * @copyright  
 * @license    
 */

class Zuora_Debug
{

    /**
     * @var string
     */
    protected static $_sapi = null;

    /**
     * Get the current value of the debug output environment.
     * This defaults to the value of PHP_SAPI.
     *
     * @return string;
     */
    public static function getSapi()
    {
        if (self::$_sapi === null) {
            self::$_sapi = PHP_SAPI;
        }
        return self::$_sapi;
    }

    /**
     * Set the debug ouput environment.
     * Setting a value of null causes Zuora_Debug to use PHP_SAPI.
     *
     * @param string $sapi
     * @return void;
     */
    public static function setSapi($sapi)
    {
        self::$_sapi = $sapi;
    }

    /**
     * Debug helper function.  This is a wrapper for var_dump() that adds
     * the <pre /> tags, cleans up newlines and indents, and runs
     * htmlentities() before output.
     *
     * @param  mixed  $var   The variable to dump.
     * @param  string $label OPTIONAL Label to prepend to output.
     * @param  bool   $echo  OPTIONAL Echo output if true.
     * @return string
     */
    public static function dump($var, $label=null, $echo=true)
    {	
    		global $debug;
    		if($debug){
	        // format the label
	        $label = ($label===null) ? '' : rtrim($label) . ' ';
	
	        // var_dump the variable into a buffer and keep the output
	        ob_start();
	        var_dump($var);
	        $output = ob_get_clean();
	
	        // neaten the newlines and indents
	        $output = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $output);
	        if (self::getSapi() == 'cli') {
	            $output = PHP_EOL . $label
	                    . PHP_EOL . $output
	                    . PHP_EOL;
	        } else {
	            $output = '<pre>'
	                    . $label
	                    . htmlspecialchars($output, ENT_QUOTES)
	                    . '</pre>';
	        }
	
	        if ($echo) {
	            echo($output);
	        }
	        return $output;
	      }
    }

}
