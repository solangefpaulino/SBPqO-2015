<?php

if (isset($argv[0])) {
	// TERMINAL:
	array_shift($argv);
	if (isset($argv[0]) && substr($argv[0],0,1)=='-') {
		$MODO = strtolower( substr($argv[0],1) );
		array_shift($argv);
	}
	$file = empty($argv)? 'php://stdin': $argv[0];
} else {
	// ONLINE:
	$file = isset($_REQUEST['file'])? $_REQUEST['file']: './example1.htm';
}


if (substr($MODO,0,4)=='tudo') {
	file_put_contents('php://stderr', "\n-- rodando COM LOOP 'TUDO', modo $MODO\n");
	$dir = $file;
	$MODO = substr($MODO,5);
	$isXML = (substr($MODO,0,4)=='final'); 

	if ($isXML)
		print "FALTA-XML-HEAD\n<root tool_vers='$LIBVERS' modo='$MODO'>\n";
	else
		print "\n == TOOLS (main.php) v$LIBVERS modo $MODO==";
	$lista = array();
	foreach (scandir($dir) as $file) if (!is_dir($file)) {
		$lista[$file]=1;
		$ffile = "$dir/$file";
		if (substr($MODO,0,5)=='relat') 
			print "\n== $ffile ==";
		$dom = new domParser();
		if ( $dom->getHtmlBody($ffile) )
			print $dom->output($MODO,$finalUTF8) . "\n\n";
		else 
			$lista[$file]=0;
	} // for
	if ($isXML)
		print "\n\n</root>\n";
	else {
		print "\n\n--- TOTAIS ---\n".count(array_keys($lista))." arquivos analisados:";
		foreach($lista as $k=>$v) print "\n\t $k = ".($v? "sucesso": "FALHOU NA LEITURA DO ARQUIVO HTML");
		print "\n";		
	}

} else {
	file_put_contents('php://stderr', "\n-- rodando sem o TUDO, modo $MODO\n");
	$dom = new domParser();
	if ( $dom->getHtmlBody($file) )
		print $dom->output($MODO,$finalUTF8);
}


/**
 * ASSERTS.
 * USO:
 * $ php assert.php 
 * $ php assert.php -gen 
 */
$VERSAO = '1.0'; // v1.0 de 2014-08-03 
assert_options( ASSERT_CALLBACK, 'comunicaErro');


echo "---- ASSERT (v$VERSAO): COMPARANDO EXECUSSAO COM HOMOLOGADOS ---\n pwd = $baseDir\n";

die("DEBUG");

#### testa todas as options sem loop ("tudo" desligado)
//rm tools/assert
//php main.php -relat material/originais2014-01-01/html/AO.html > assert
//diff tools/assert tools/assert1.txt

//php tools/main.php -relat2 material/originais2014-01-01/html/AO.html > assert

//php tools/main.php -xml material/originais2014-01-01/html/AO.html > assert

//php tools/main.php -finalXml material/originais2014-01-01/html/AO.html > assert

//php tools/main.php -finalHtml material/originais2014-01-01/html/AO.html > assert



/**
 * assert_callback.
 */
function comunicaErro( $script, $line, $message ) {
	echo "Essa nova versão de main.php tem algum erro ou incompatibilidade\n Veja<b> $script</b>: line <b>$line</b> :<br/>";
	echo '<b>', ereg_replace( '^.*//\*', '', $message ), '</b><br /><br />';
	echo 'Confira o código-fonte do main.php.';
	exit;
}

?>

