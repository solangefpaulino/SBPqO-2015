<?php
/**
 * RESUMOS, parser e analisador.
 * USO:
 *  $ cd projeto
 *  $ php tools/main.php --xml --in=material/originais-UTF8/ > SBPqO_step1.xml
 *  $ php tools/main.php --finalXml --day=2014-09-03  --in=material/originais-UTF8/ > SBPqO_dia09-03.xml
 *  $ php tools/main.php -h
 */

include('lib.php');


$cmd = array_pop($io_options_cmd); // ignora demais se houver mais de um
if (!$cmd)
	die( "\nERRO, SEM COMANDO\n" );
elseif (count($io_options_cmd)) {
	die("\nERRO, MAIS DE UM COMANDO, SÓ PODE UM\n");
}
if ($cmd == 'help')
	die($io_usage);
elseif ($cmd=='version')
	die(" lib.php version $LIBVERS\n");


$isXML = ($cmd=='xml' || $cmd=='finalXml' || $cmd=='raw');
$isRELAT = (substr($cmd,0,5)=='relat');


$file = 'php://stdin';
if ( isset($io_options['in']) ) {
	$file = $io_options['in'];
	if (is_dir($file)) {
		$file = trim($file);
		if (substr($file,-1,1)!='/') 
			$file.='/';
		$OUT = '';
		$LINE = ($isXML || $isRELAT)? "\n": '';
		foreach(scandir($file) as $f) if (preg_match('/\.html?$/i',$f)) {
				$OUT .= exec_cmd($cmd,"$file$f",$isRELAT) . $LINE;
		} // for if
		$file ='';
	} // if
} // if
if ($file)
	$OUT = exec_cmd($cmd,$file,$isRELAT);

print_byTPL($OUT,$isRELAT,$isXML);
if ($isRELAT)
	print "\n\nTOTAL GERAL: $NTOTAL\n";



/////////

function print_byTPL($OUT,$isRELAT,$isXML,$tag='html') {
	global $cmd;
	global $io_options;	
	if ($isXML) {
		if ($cmd == 'xml')
			print XML_HEADER1."\n<$tag>$OUT\n</$tag>\n";
		else
			print XML_HEADER1.$OUT;
	} elseif (!$isRELAT) {
		$FP = (isset($io_options['firstpage']))? "body {counter-reset: page {$io_options['firstpage']};}": '';
		$TPL = file_get_contents('tools/xsl/HTML_STANDARD_F1.html');
		$TPL = str_replace(array('{_ALL_SECTIONS_}','{_FIRSTPAGE_}'), array($OUT,$FP), $TPL);
		print $TPL;
	} else
		print $OUT;
}

function exec_cmd($cmd,$file,$isRELAT,$rmHeader=1,$finalUTF8=true) {
	global $io_options;
	global $dayFilter;

	file_put_contents('php://stderr', "\n -- ($cmd) $file\n");
	if ($isRELAT)	
		print "\n=== $cmd  $file ===";
	$dayFilter = isset($io_options['day'])? $io_options['day']: '';	
	$doc = new domParser();
	$doc->getHtmlBody($file, isset($io_options['utf8']) && $io_options['utf8']);
	$out = $doc->output($cmd,$finalUTF8,$dayFilter);
	
	if (!$isRELAT)	{
		if ($rmHeader) $out = str_replace(XML_HEADER1,'',$out);
		$out = trim($out);
	}

	if (!isset($io_options['breaklines'])) // na verdade no-breaklines
		$out = str_replace(
			array('<p',  '<div',  '<article',  '<sec',  '<keys', '<days'), 
			array("\n<p","\n<div","\n<article","\n<sec","\n<keys","\n<days"),
			$out
		);

	if (isset($io_options['normaliza'])){ // normaliza texto do autor!
		$out = preg_replace('/(\d)\s+±\s+(\d)/us','$1&#8239;±&#8239;$2', $out);
		$out = preg_replace('/([\dp])\s*(&lt;|&gt;|=)\s*([\dp])/ius','$1&#8239;$2&#8239;$3', $out);
	}
	if (isset($io_options['entnum']))
		$out = utf2html($out);	
	return "$out\n";
}

?>