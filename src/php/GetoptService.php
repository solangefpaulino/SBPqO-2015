<?php
/**
 * Futura classe GetoptService. Unifica a descrição de serviços no console, JSON-RPC, XML-RPC (WSDL).
 * EM CONSTRUCAO
 */

function getopt_FULLCONFIG(
	$io_longopts_full, // array
	$io_usage,    // string
) {

	$io_isCmd    = array();
	$io_longopts = array();
	$io_params   = array();
	$io_stropts = '';
	foreach($io_longopts_full as $k=>$v) {
		if (preg_match('/^([a-z0-9])\|([^:\*]+)(:[^\*]+)?(\*)?$/i',$k,$m)) {  	// ex. "o|out:file"
			$k2 = $m[2];
			$io_optmap[$m[1]] = $k2;
			if ($m[3]) {
				$flag = (substr($m[3],0,2)=='::')? '::': ((substr($m[3],0,1)==':')? ':': '');
				$opt = $flag? str_replace($flag,'',$m[3]): '';
				$io_params[$k2]=$m[3];
			} else
				$flag = '';
			$io_stropts .= "$m[1]$flag";
			if ($m[4])
				$io_isCmd[]=$k2;
		} else  // CUIDADO, não esta tratando  ex. "out:file"
			$k2=$k;  // $io_params[$k] = '';
		$io_longopts["$k2$flag"]=$v;
	}
	$io_usage   = getopt_msg($io_usage,$io_longopts);
	$io_options = getopt($io_stropts, array_keys($io_longopts)); // is for terminal; for http uses $_GET of the same optes
	$io_options_cmd = array();
	foreach ($io_options as $k=>$v) {
		if ($v===false)  // facilitador de pesquisa	
			$io_options[$k]=true;
		if ( isset($io_optmap[$k]) ) {   // normalizador (para long-options)
			unset($io_options[$k]);
			if ( !isset($io_options[$io_optmap[$k]]) )
				$io_options[$io_optmap[$k]]=true;
		}
	}
	foreach ($io_options as $k=>$v) // preserva apenas os comandos
		if (in_array($k,$io_isCmd))
			$io_options_cmd[] = $k;	
	return array($io_options,$io_usage);
} // func


/**
 * Replace placeholder template ($tpl) _MSG_ variables by respective's line --KEY $longopts VALUES.
 * EXAMPLE OF $tpl fragment for getopt_msg() formating:
 *    -f <file>
 *    --file=<file>   _MSG_ 
 */
function getopt_msg($tpl,$longopts) {
	// falta str_replace do __DESCRIPTION__ e __PARAMETERS__ que já montam tudo.
	return preg_replace_callback(
		'/\-\-([a-zA-Z_0-9]+)(.+?)(_MSG_)/s',
		function($m) use ($longopts) {
			if (isset($longopts[$m[1]]))
				$s = $longopts[$m[1]];
			elseif (isset($longopts["$m[1]:"]))
				$s = $longopts["$m[1]:"];
			if ($s)
				return "--$m[1]$m[2]$s";			
			else
				return $m[1].$m[0];
		},
		$tpl
	);
}

?>

