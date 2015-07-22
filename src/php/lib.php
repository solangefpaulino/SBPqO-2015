<?php
/**
 * RESUMOS, parser e analisador.
 * Uso:
 *  $ cd pdfGenerator/clientes/BOR/SBPqO_resumos
 *  $ php tools/main.php --help
 * ATENCAO: o mais correto é armazenar itens no PostgreSQL e gerencia-los por lá.
 *   Por hora fazendo o possível com xsl_nRegister().
 */

//setlocale (LC_COLLATE, 'pt_br');//
// tb funciona: setlocale( LC_ALL, 'pt_BR.utf-8', 'pt_BR', 'Portuguese_Brazil');
date_default_timezone_set('Brazil/East');
setlocale(LC_ALL,'pt_BR.UTF8');
mb_internal_encoding('UTF8'); 
mb_regex_encoding('UTF8');

$NTOTAL=0;
$dayFilter = $dayLocais = ''; // para secao corrente

define ('XML_HEADER1', '<?xml version="1.0" encoding="UTF-8"?>');
$LIBVERS = '1.5'; // v1.4 de 2014-08-24; v1.3 de 2014-08-12; v1.2 de 2014-08-03; v1.1 de 2014-08-02; v1.0 de 2014-08-01.

/*
 OUTROS SUBSIDIOS PARA O "PROJETO GETOPT-WSDL":
 Revisar: 
  https://github.com/ulrichsg/getopt-php
  http://pear.php.net/package/Console_GetoptPlus  
  https://github.com/hguenot/GWebService/blob/master/command/WsdlCommand.php
    https://github.com/hguenot/GWebService/blob/master/command/wsdl/assets/Getopt.php
  Outras refs:
    http://stackoverflow.com/a/1023142/287948
    https://github.com/jcomellas/getopt
*/

///////  ///////  ///////  /////// 
/////// I/O INICIALIZATION ///////
$io_baseDir = realpath( dirname(__FILE__).'/..' );  // não usar getcwd();

// CONFIGS:
$fileDescr = "$io_baseDir/material/originais-UTF8/indice_descritores.csv";
$fileLocalHora = "$io_baseDir/material/originais-UTF8/localHorario.csv";
$fileField00 = 'COD_CHAVE';
$buffsize = 3000;

$MODO = 'extract';
$finalUTF8 = 1;
$SECAO = array(
	'PE'=>'Pesquisa em Ensino',
	'PO'=>'PROJETO POAC (Projeto de Pesquisa Odontológica de Ação Coletiva)',
	'PR'=>'PRONAC - Prêmio Incentivo A Pesquisa - Produtos Nacionais',	
	'HA'=>'UNILEVER Travel Award (Hatton)',
	'COL'=>'Prêmio COLGATE Odontologia Preventiva',
	'AO'=>'Apresentação Oral',
	'FC'=>'Fórum Científico',
	'PI'=>'Painel Iniciante (prêmio Miyaki Issao)',	
	'PN'=>'Painel Aspirante e Efetivo',
);

// REVISAR NECESSIDADE DESSAS GLOBAIS E SUA UTILIZACAO
$DESCRITORES = array();
$DESCR_byResumo = array();
$DESCR_bySec = array();
$DESCR_resumoList= array();
$LocHora_byResumo= array();
$Resumos_byDia = array();
$dayLocais_bySec = array();

// DESCRITORES DE CADA RESUMO:	
if (($handle = fopen($fileDescr, "r")) !== FALSE) {
	$tmp = fgetcsv( $handle, $buffsize, ';');
	if ($tmp[0]!=$fileField00)  // caracteristica
		die("\nERRO343 em $fileDescr\n{$tmp[0]}\n");
    while (($tmp = fgetcsv($handle, $buffsize, ';')) !== FALSE) {
    	$resumos = preg_split('/[ ,]+/', $tmp[2]);
    	$DESCRITORES[$tmp[1]] = $resumos;
    	foreach ($resumos as $rid) {
    		if (isset($DESCR_byResumo[$rid]))
    			$DESCR_byResumo[$rid][] = $tmp[1];
    		else
    			$DESCR_byResumo[$rid] = array($tmp[1]);
    	}
    } // while
    fclose($handle);
    foreach ($DESCR_byResumo as $rid => $lst) { // ordena itens
    	$lst = array_unique($lst);
    	usort($lst,'strcoll');
    	$DESCR_byResumo[$rid] = $lst; // join('; ',$lst);
    }
} // if
// LOCAL E HORA DE CADA RESUMO:
if (($handle = fopen($fileLocalHora, "r")) !== FALSE) {
	//$tmp = fgetcsv( $handle, $buffsize, ';');
    while (($tmp = fgetcsv($handle, $buffsize, ';')) !== FALSE) 
      if (strlen($tmp[0])>2 && $tmp[0]!='SIGLA') {
    	//Exemplo:
    	//  PR0001;2014-09-03;8:30 às 12:00 h;Sala Novara – 10º andar
    	//  0=rid; 1=dia;     2=intervalo;    3=local
    	// HA001;2014-09-04;8:30 – 12:00 h;Sala Camerino – 1º andar

    	$rid = preg_replace('/0(\d\d\d)/','$1',$tmp[0],1); // remove 0s em excesso    	
    	$hini = $hfim = $hini2 = $hfim2 ='';
    	if (preg_match('#^\s*([\d:]+)[^\d:]+([\d:]+)[\shs]*/\s*([\d:]+)[^\d:]+([\d:]+)[\shs]*$#su', $tmp[2], $m)) {
    		//    9:00 – 12:00 h / 13:30 - 17:30 (caso exótico!)
    		list($hini,$hfim,$hini2,$hfim2) = array($m[1],$m[2],$m[3],$m[4]);
			$hini = "$hini;$hini2";
			$hfim = "$hfim;$hfim2";
    	} elseif (preg_match('/^\s*([\d:]+)[^\d:]+([\d:]+)[ hs]*$/su', $tmp[2], $m))
    		list($hini,$hfim) = array($m[1],$m[2]);
    	//if ($rid>'') die("debugd $rid d$hini,$hfim,$tmp[1]");
    		$local = preg_replace('/([\-–])/u', ' $1 ', $tmp[3]);
    		$local = trim(preg_replace('/\s+/', ' ', $local));
    	$LocHora_byResumo[$rid] = array($tmp[1],$hini,$hfim,$local); // dia, hora-inicial, final, local
    	//print "--debug $tmp[0]";
    	//var_dump($LocHora_byResumo);
    	if (!isset($Resumos_byDia[$tmp[1]]))
    		$Resumos_byDia[$tmp[1]]=array();
   		//print "\n debug $tmp[1] $rid";
    	$Resumos_byDia[$tmp[1]][] = $rid;

    	$sec = xsl_splitSecao($rid,1);
    	$daysec = "$tmp[1]#$sec";
    	if (!isset($dayLocais_bySec[$daysec]))
    		$dayLocais_bySec[$daysec]=array();
    	$dayLocais_bySec[$daysec][$local] = 1;
      } // while
    fclose($handle);
    foreach($dayLocais_bySec as $daysec=>$a)
    	$dayLocais_bySec[$daysec] = join('; ',array_keys($a));
} // if


list($io_options,$io_usage,$io_options_cmd,$io_params) = getopt_FULLCONFIG(
	array(
	    "1|relat1*"=>	'shows a input analysis partial relatory',
	    "2|relat2*"=>	'shows a input analysis complete relatory, listing elements',
	    "3|relat3*"=>	'shows a ID list',

	    "r|raw*"=>     	 'outputs RAW input HTML',	
	    "x|xml*"=>     	 '(default) outputs a raw (non-standard) XML format, for debug',	
	    "m|finalXml*"=>	 'outputs a final (standard) XML JATS-like format',	
	    "l|finalHtml*"=> 'outputs a final (standard) XHTML format',	

	    "o|out:file"=>		'output file (default STDOUT)',
	    "f|in:file_dir"=> 	'input file or directory (default file STDIN)',
	    "e|entnum"=>    	'outputs special characters as numeric entity',
	    "u|utf8"=>    		'check and convert input to UTF-8 encode',
	    "k|breaklines"=>  	'(finalHtml) outputs without default filter of breaking lines',
	    "n|normaliza"=>  	'(finalHtml) outputs with units normalization',
	    "p|firstpage:value"=>  	'(finalHtml) first page (default 1)',
	    "d|day:value"=>  	'to select only data for one day (see xml). YYYY-MM-DD format.',

	    "v|version*"=>	'show versions',
	    "h|help*"=>   	'show help message',
	),

	"Usage: 
   php main.php [options] [--] [args...] [-f] {\$file} [-o] {\$file} 
   php assert.php [options] [--] [args...] [-f] {\$file} 
   php main.php [rexml] < {\$file_input} > {\$file_output}
   php assert.php [rexml] < {\$file_input}

   -f <file>
   --in=<file>  _MSG_
   -o <file>
   --out=<file>	_MSG_
   _DESCR_OPTIONS_

   -v
   --version	_MSG_
   -h
   --help   	_MSG_
   "
);

/**
 * Extensão da função getopt() para configuração integral das mensagens e validações. 
 * @param io_longopts_full array(opt=>descricao). Cada opt respeitando a seguinte sintaxe,
 *   	  <opt> "|" <longopt> [":"<obj>|"::"<obj>] ["*"]
 *        Onde <opt> é uma letra, <longopt> uma palavra, <obj> um indicador "file" ou "dir", 
 *        "::" opcional, ":" obrigatorio, "*" indica que é um comando (nao apenas opt de io).
 */
function getopt_FULLCONFIG(
	$io_longopts_full, // array
	$io_usage    // string
) {
	$io_isCmd    = array();
	$io_longopts = array();
	$io_params   = array();
	$io_stropts = '';
	foreach($io_longopts_full as $k=>$v) {
		if (preg_match('/^([a-z0-9])\|([^:\*]+)(:[^\*]+)?(\*)?$/i',$k,$m)) {  	// ex. "o|out:file"
			$k2 = $m[2];
			$io_optmap[$m[1]] = $k2;
			if (isset($m[3]) && $m[3]) {
				$flag = (substr($m[3],0,2)=='::')? '::': ((substr($m[3],0,1)==':')? ':': '');
				$opt = $flag? str_replace($flag,'',$m[3]): '';
				$io_params[$k2]=$m[3];
			} else
				$flag = '';
			$io_stropts .= "$m[1]$flag";
			if (isset($m[4])) // indicador de comando
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
	return array($io_options,$io_usage,$io_options_cmd,$io_params);
}


$io_isTerminal =  (isset($argv[0]) && isset($argv[1]));
if (!$io_isTerminal) {
	die("GET options em construção");
}

/////// END I/O INICIALIZATION ///////
///////   ///////   ///////   /////// 


/**
 * Parsem, replacing placeholder template ($tpl) _MSG_ variables by respective's line --KEY $longopts VALUES.
 * Make a internal copy of $longopts and changes it... Replaces also the $descNameRef placeholder.
 * @param $tpl string template
 * @param $longopts array options
 * @param $descNameRef internal definition for full-description's placeholder.
 */
function getopt_msg($tpl,$longopts,$sp1='   ',$descNameRef='_DESCR_OPTIONS_') {
	$tpl = preg_replace_callback(
		'/\-\-([a-zA-Z_0-9]+)(.+?)(_MSG_)/s',
		function($m) use (&$longopts) {
			if (isset($longopts[$m[1]])) {
				$s = $longopts[$m[1]];
				unset($longopts[$m[1]]);
			} elseif (isset($longopts["$m[1]:"])) {
				$s = $longopts["$m[1]:"];
				unset($longopts["$m[1]:"]);				
			}
			if ($s)
				return "--$m[1]$m[2]$s";			
			else
				return $m[1].$m[0];
		},
		$tpl
	);  // if remain longopts, continue...
	if ( strpos ($tpl,$descNameRef)!==false ){ // placeholder exists?
		$DESCR = '';
		$fill = 16;
		foreach ($longopts as $k=>$v) if ($k) {
			//if (preg_match('/^([a-z0-9])\|([^:\*]+)(:[^\*]+)?(\*)?$/i',$k,$m)) {
			// $DESCR.="\n$sp1-$m[1]\n\t--".str_pad($m[2],$fill ,' ');
			$DESCR.="\n$sp1--".str_pad($k,$fill ,' ');
			$DESCR.=$v;
		}
		$tpl = str_replace($descNameRef,$DESCR,$tpl);
	}
	return $tpl;
}

//////// LIB ////////

/**
 * DOMDocument parser, converts to XML SJATS format.
 */
class domParser extends DOMDocument { // refazer separando DOM como no RapiDOM!
	public $contentArray=array();
	public $css;
	public $newDom = NULL;
	public $isXML_step1 = false; // se true significa que não é HTML

	private $nodePathes = array(
		'BRs'=>'//br',
		'paragrafos P'=>'//p',
		'  vazios'=>'//p[not(normalize-space(.))]',		
		'  não-vazios'=>'//p[string-length(string(.))>0]',
		'    com 200+ letras'=>'//p[string-length(string(.))>200]',
		'    com 1800+ letras'=>'//p[string-length(string(.))>1800]',
		'    com 2500+ letras'=>'//p[string-length(string(.))>2500]',
		'  com BR'=>'//p[.//br]',
		'    com 5 BRs'=>'//p[count(.//br)=5]',
		'    com 6 BRs'=>'//p[count(.//br)=6]',
		'    com 7 BRs'=>'//p[count(.//br)=7]',
		'    com 8 BRs'=>'//p[count(.//br)=8]',
		'  com span'=>'//p[.//span]',
		'    com span.class'=>'//p[.//span/@class]',
		'  com formatação (I,B,etc.)'=>'//p[.//i or .//em or .//b or .//strong or .//sup or .//sub]',
		'itens (volume em nodes)'=>'//node()',
	);
	private $CRLFs = "\n\n";

	/**
	 * Get HTML body from any HTML file. Use show_*() methods to check it before to use. 
	 */
	function getHtmlBody($fileOrString, $enforceUtf8=false) {
		$this->resolveExternals = true;
		$this->preserveWhiteSpace = false;  // com false baixou de 1510 nodes para 1101, ou seja ~25% num JATS tipico.
		// ver também DTD HTML e  xml:space="preserve"

	  	if ((strlen($fileOrString) < 300) && (strpos($fileOrString,'<') === false))
	  		$fileOrString = file_get_contents($fileOrString);
		if ($enforceUtf8) {
			$enc = mb_detect_encoding($fileOrString,'ASCII,UTF-8,UTF-16,ISO-8859-1,ISO-8859-5,ISO-8859-15,Windows-1251,Windows-1252,ISO-8859-2');
			if ($enc!='UTF-8') // ex. ISO-8859-1  Windows-1251,Windows-1252
				$fileOrString = mb_convert_encoding($fileOrString,'UTF-8',$enc); //$enc);
		}
		$this->encoding = 'UTF-8';

		if (!preg_match('/^\s*<\?xml\s/',$fileOrString)) {
			$cc=0;// IMPORTANTE O META, SENAO loadHTML IGNORA!!
			$fileOrString = preg_replace(
				'/(<meta)/is', 
				'<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />$1', 
				$fileOrString, 1, $cc
			);  // garante UTF8 do XHTML. Cuidado, vale apenas (X)HTML, não outros como body isolado. 
			if (!$cc)
			    $fileOrString = '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />'.$fileOrString;
			$this->recover =true;
			$fileOrString = str_replace('<0','&lt;0',$fileOrString); // GAMBI! Tidy!
			// tratar demais casos de lt gt incorretos!		
			@$this->loadHTML($fileOrString, LIBXML_NOWARNING | LIBXML_NOERROR);
			$this->css  =  	$this->getElementsByTagName('style')->length? 
								$this->getElementsByTagName('style')->item(0)->textContent: 
								'';
			if ($this->getElementsByTagName('body')->length) {
				$XML = 	$this->saveXML( $this->getElementsByTagName('body')->item(0) );
				$XML = preg_replace('#^\s*<body[^>]*>|</body>\s*$#','',$XML);
				$XML = "<html>$XML</html>";
			} else
				$XML = 	$this->saveXML($this->documentElement); // html root?					
		} else {
			$XML = &$fileOrString; // veio mesmo XML
			$this->isXML_step1 = true;
		}
		if (trim($XML)) {
			$this->recover =true;
			$this->loadXML($XML);
			$this->encoding = 'UTF-8';
			$this->normalizeDocument();
			return true;
		} else
			return false;
	}

	function setItem(&$list,$p) {
		if (!isset($list[$p])) $list[$p]=0; 
		$list[$p]++;
	}

	function show_nodePathes() {
		print "{$this->CRLFs}---- show_nodePathes nodePathes and attribs: ---";
		$list = array();
		$attr = array();
		$xp = new DOMXpath($this);
		foreach($xp->query("//*") as $node) {
			$p = preg_replace('/\[\d+\]/','',$node->getNodePath());
			$this->setItem($list,$p);
			$s = $node->getAttribute('class');
			if ($s) $this->setItem($list,"$p.class($s)");
		}
		foreach($list as $k=>$v)
			print "\n\t$k=$v";
	}

	function show_xpathes() {
		print "{$this->CRLFs}---- show_xpathes count types: ---";
		$xp = new DOMXpath($this);
		foreach($this->nodePathes as $rotulo=>$path)
			print "\n\t$rotulo: ".$xp->evaluate("count($path)");
	}

	function show_cssParts() {
		print "{$this->CRLFs}---- show_cssParts relevant lines: ---";
		foreach (explode("\n",$this->css) as $line)
		  if (preg_match('/font\-style|vertical\-align|font\-family\s*:[^\}]*SYMBOL/is',$line))
			print "\n\t".trim($line);
	}

	function show_extractionSumary($onlyIds=false) {
		$IDs = array();
		$RELAT = "{$this->CRLFs}---- show_extractionSumary: ---";
		$xp = new DOMXpath($this);
		$n=0;
		foreach($xp->query('//p') as $node) {
			$id='';
			$n++;
			$txt = $node->nodeValue;
			if (preg_match('/^\s*([A-Z]+\-?[a-z]?[0-9]{1,5})/su',$txt,$m) && $id=$m[1]) {
				$IDs[]=$id;
				$ntype = array();
				$first='';
				foreach($xp->query('.//node()',$node) as $subnode) { // ERRO, não é um taverse, requer recorrencia.
					$this->setItem( $ntype, $subnode->nodeName ); // .{$subnode->nodeType}
					if ($subnode->nodeName=='#text' && !$first) $first=trim($subnode->nodeValue);
				}
				$RELAT.= "\n\tlinha $n, pubid $id: ";
				if ($first!=$id)
					$RELAT.= " !diff id $first!=$id! ";
				foreach ($ntype as $k=>$v) $RELAT.= "$k=$v; ";
			} else 
				$RELAT.= "\n\t!paragraph $n ERROR! content of 30 firsts: '".substr($txt,0,30)."'";
		}
		print $onlyIds? ("\n".join('; ',$IDs)."\n TOTAL $n\n") : $RELAT;
		global $NTOTAL;
		$NTOTAL+=$n;
		return '';
	}

	// gera step1
	// NAO USA MAIS dayFilter, arrumar
	function asXML($dayFilter='') {
		global $Resumos_byDia;
		global $DESCR_byResumo;
		global $LocHora_byResumo;
		if (!$this->isXML_step1) {
			$XML = "\n";
			// DEPOIS AINDA PASSAR POR UM NORMALIZADOR XSLT!
			$replacElements = array('pubid','title','contribs','aff','corresp','abstract','conclusion','ERRO');
			$replacElements_n = count($replacElements);
			$this->newDom = new DOMDocument();
			$root = $this->newDom->createElement('root');
			$this->preserveWhiteSpace = false; // XML_PARSE_NOBLANKS
			$xp = new DOMXpath($this);
			$n=0;
			$nOk=0;
			$secs = array();
			$subsecs = array();
			$dias = array();
			$locais = array();		
			foreach($xp->query('//p') as $node) {
				$id='';
				$n++;
				if (  preg_match('/^\s*(([A-Z]+)(\-?[a-z]?)[0-9]{1,5})/su',$node->nodeValue,$m) 
					  && ($id=$m[1])
					) {
					// fora de uso && (!$dayFilter || in_array($id,$Resumos_byDia[$dayFilter]))
					$sec = $m[2];
					$secs[$sec] = 1;
					$subsecs[$m[3]] = 1;
					$nOk++;
					$DESCR = isset($DESCR_byResumo[$id])? $DESCR_byResumo[$id]:  array("(sem descritor de assunto)");
					if (!isset($LocHora_byResumo[$id]))
						list($dia,$hini,$hfim,$local) =  array('err','err','err','err');
					else
						list($dia,$hini,$hfim,$local) = $LocHora_byResumo[$id]; // 0=dia, 1=hora-inicial, 2=final, 3=local
					$dias[$dia] = 1;
					$locais[$local] = 1;

					$nEle = $ntexts = 0;
					$auxDom = new DOMDocument();
					$art = $auxDom->createElement('article');
					$ele = $auxDom->createElement($replacElements[$nEle]);
					$art->appendChild($ele); // o primeiro já é iniciado

					$ele2 = $auxDom->createDocumentFragment();
					$per2 = '';
					if (strpos($hini,';')!==false) {
						list($hini,$hini2) = explode(';',$hini);
						list($hfim,$hfim2) = explode(';',$hfim);
						$per2 = "<period><start day=\"$dia\">$hini2</start><end>$hfim2</end></period>";
					}
					$event2='';
					if 	($sec=='PN') {
						$event2 = "<event2><summary>Reunião de Grupo</summary><period><start day=\"$dia\">17:30</start><end>19:00</end></period><location>$local</location></event2>";
						$local = "Salão Monumentalle";					
					}
					$ele2->appendXML(
						"<vcalendar><components>"
						."<period><start day=\"$dia\">$hini</start><end>$hfim</end></period>"
						.$per2
						."<location>$local</location>"
						.$event2
						."</components></vcalendar>"
					);
					$art->appendChild($ele2); // o primeiro já é iniciado

					$ele2 = $auxDom->createDocumentFragment();
					$ele2->appendXML( '<keys><key>'.join('</key><key>',$DESCR).'</key></keys>' );
					$art->appendChild($ele2);

					foreach ($node->childNodes as $subnode) {
						// PARSER: split by BR, analyse and add elements
						$nname = $subnode->nodeName;	
						if ($nname!='br') {
							if ($nname=='#text') { // right-trim of the first text-node
								$text = preg_replace('/\s+/us',' ',$subnode->nodeValue); // normalize spaces
								if ($ntexts)
									$ele->appendChild( $auxDom->createTextNode($text) );								
								else {							
									$ntexts=1;
									$ele->appendChild( $auxDom->createTextNode(rtrim($text)) );
								}
							} else {  // demais nodes
								$imp = $auxDom->importNode($subnode, true);
								$ele->appendChild($imp);							
							}
						} else {
							$nEle++;
							$ntexts=0;
							if (!isset($replacElements[$nEle]) || $replacElements[$nEle]=='ERRO') {
								$ele = $auxDom->createElement('ERRO');
								$ele->setAttribute('linha',$n);
								$ele->setAttribute('tipo',"BR $nEle imprevisto");
							} else
								$ele = $auxDom->createElement($replacElements[$nEle]);
							$art->appendChild($ele);
						} // else
					} // for childNodes
					$P = $auxDom->saveXML($art);
					$XML.= "\n\n$P";
				} // if
			} //for

			$locais = array_keys($locais);
			$local = count($locais)? '<location>'.join('</location><location>',$locais).'</location>': '<error/>';

			$dias = array_keys($dias);
			$dia = count($dias)? '<day>'.join('</day><day>',$dias).'</day>': '<error/>';

			$secs = array_keys($secs);
			$sec = (!count($secs) || count($secs)>1)? 'ERROR': $secs[0];
			$subsecs = array_keys($subsecs);		
			$subsec = (count($subsecs))? $subsecs[0]: '';	
			global $SECAO;
			$SECAO_ordem=array_flip(array_keys($SECAO)); // ex. $SECAO_ordem['PR']==2.
			$title = isset($SECAO[$sec])? $SECAO[$sec]: 'ERROR';			
			$sord = isset($SECAO_ordem[$sec])? $SECAO_ordem[$sec]: 'ERROR';
			//if ($subsec) $title.=", Parte \"$subsec\"";
			$err = ($n!=$nOk)? "<ERRO-GRAVE>lidos $n paragrafos, usados $nOk!</ERRO>":'';
			$XML = "<sec id=\"$sec$subsec\" label=\"$sec\" sec-order=\"$sord\" subsec=\"$subsec\" sec-type=\"modalidade\">
				$err
				<title>$sec$subsec - $title</title>
				<days>$dia</days>
				<locations>$local</locations>
				\n$XML\n</sec>\n";
			return $XML;

		//} elseif ($dayFilter) { // já é XML, falta só grep por dia
			// nao precisou pois finalXml faz grep!
		} else
			return $this->saveXML();

	} // func


	/**
	 * Saída XML-padrão (campos normalizados, tags e atributos padronizados).
	 * Traduz a saída do método asXML() para quase que um JATS.
	 * @param $MODO string 'dom' ou 'xml', designa o tipo de retorno.
	 */
	function asStdXML($MODO='dom',$dayFilter='') {
		// criar boolean para match="article[fn:useThisId(string(@id),local,dia, keys)]"
		// vai listar em array apenas os resumos e seu local e dia
		$XSL = "<xsl:param name=\"dayFilter\">$dayFilter</xsl:param>\n".'

			<xsl:template match="ERRO[@tipo=\'BR 7 imprevisto\']" /><!-- limpa warnings -->

			<xsl:template match="/">
				<html>
				<xsl:apply-templates select=".//sec">
					<xsl:sort select="@sec-order" data-type="number" />
					<xsl:sort select="@subsec" />
				</xsl:apply-templates>
				</html>
			</xsl:template>

			<xsl:template match="sec">
				<xsl:if test="$dayFilter=\'\' or ./days/day=$dayFilter">
				  <xsl:copy><xsl:copy-of select="@*"/>
				  	<xsl:apply-templates select="title"/>
					<xsl:choose>
						<xsl:when test="$dayFilter=\'\'">
						    <xsl:apply-templates select="days|locations"/>
						    <keys todo="1"/>		<!-- must be unique and ordered -->						    
					    	<xsl:apply-templates select=".//article"/>
						</xsl:when>
						<xsl:otherwise>
						    <days><day><xsl:value-of select="$dayFilter" /></day></days>
						    <locations todo="1"/>	<!-- must be unique and ordered -->
						    <keys todo="1"/>		<!-- must be unique and ordered -->
						    <xsl:apply-templates select=".//article[.//start/@day=$dayFilter]"/>
						</xsl:otherwise>
					</xsl:choose>
				  </xsl:copy>
				</xsl:if><!-- else discard -->
			</xsl:template>


			<!-- some article elements: -->

			<xsl:template match="article">
				<xsl:copy>
					<xsl:copy-of select="@*"/>
					<xsl:attribute name="id"><xsl:value-of select="./pubid" /></xsl:attribute>
					<xsl:attribute name="secid">
						<xsl:value-of select="fn:function(\'xsl_splitSecao\',string(./pubid),-1)" />
					</xsl:attribute>
					<xsl:apply-templates/>
				</xsl:copy>
				<!-- unique registering: -->
				<xsl:copy-of select="fn:function(\'xsl_nRegister\', \'ev2\', string(./pubid), .//event2/location)" />				
				<xsl:copy-of select="fn:function(\'xsl_nRegister\', \'loc\', string(./pubid), .//components/location)" />
				<xsl:copy-of select="fn:function(\'xsl_nRegister\', \'key\', string(./pubid), .//key)" />				
			</xsl:template>

			<xsl:template match="aff"><!-- perigo, agrupar sob artigo -->
				<aff-group><xsl:copy-of select="." /></aff-group>
			</xsl:template>

			<xsl:template match="contribs">
				<xsl:copy-of select="fn:function(\'xsl_splitContrib\',.)" />
			</xsl:template>

			<xsl:template match="corresp">
				<xsl:copy-of select="fn:function(\'xsl_markCorresp\',.)" />
			</xsl:template>
		'; // <vcalendar xmlns='urn:ietf:params:xml:ns:xcal'>  ver https://tools.ietf.org/html/rfc6321 

		$xmlDom = new DOMDocument('1.0', 'UTF-8');
		$xmlDom->loadXML( $this->asXML($dayFilter) );  // redundancia se já no this.
		$xmlDom->encoding = 'UTF-8';
		$xmlDom = transformId_ToDom($XSL,$xmlDom);
		$xmlDom->encoding = 'UTF-8';

		$XSL= '
			<xsl:template match="sec[not(.//article)]|subsec[not(.//article)]" priority="9"/>

			<xsl:template match="sec/keys[@todo]">
				<xsl:copy-of select="fn:function(\'xsl_regRestore\',\'key\', string(../@id))" />
			</xsl:template>
			<xsl:template match="sec/locations[@todo]">
				<xsl:copy-of select="fn:function(\'xsl_regRestore\',\'loc\', string(../@id))" />
			</xsl:template>
			<xsl:template match="sec/days/day">
				<xsl:copy-of select="fn:function(\'xsl_dayFormat\',string(.))" />
			</xsl:template>			

		';
		$xmlDom = transformId_ToDom($XSL,$xmlDom);
		$xmlDom->encoding = 'UTF-8';
		//global $registerLists; var_dump($registerLists); 
		//die('DEBU11:'.$xmlDom->saveXML());

		return ($MODO=='dom')? $xmlDom: $xmlDom->saveXML();
	}



	/**
	 * Saída XML-padrão (campos normalizados, tags e atributos padronizados).
	 * Traduz a saída do método asStdXML() para quase que um hSJATS, em XHTML.
	 * @param $MODO string 'dom' ou 'xml', designa o tipo de retorno.
	 * @param $xmlDom DOMDocument, reusa a saida de asStdXML('dom'). 
	 */
	function asStdHtml($MODO='xml', $xmlDom=NULL, $allFlor=true, $dayFilter='') {

		if ($MODO=='dom')
			die("ERRO3844: modo DOM desativado.");
		if ($xmlDom===NULL)
			$xmlDom = $this->asStdXML('dom',$dayFilter);
		$XSLfile = $dayFilter? 'resumosS1_toHtmlF1day': 'resumosS1_toHtmlF2all';
		$xmlDom = transformToDom("tools/xsl/$XSLfile.xsl",$xmlDom); // transformId_ToDom($XSL,$xmlDom);
		$xmlDom->encoding = 'UTF-8'; // importante para saveXML nao usar entidades.
		if ($MODO=='xml'){
			// GAMBI1: com o XSLT transformando &#160; em branco comum, foi preciso gambiarra! ver ♣.
			// GAMBI2: o certo era percorrer elementos e alterar textos por DOM... string XML mais facil. 
			$xml = $xmlDom->saveXML();
			if ($allFlor)
				$xml = str_replace('♣','&#160;',$xml);
			else // evita risco de remover flor que realmente era flor, verifica apenas entre-nomes.
				$xml = preg_replace('/(?<=\p{L})♣(?=\p{L})/us', '&#160;', $xml);
			return $xml;
		} else
			$xmlDom;
	}


	function output($MODO,$finalUTF8=true,$dayFilter) {
		$MODO = strtolower($MODO);
		if ($MODO=='relat3')
			return $this->show_extractionSumary(true); // faz print

		elseif ($MODO=='relat1' || $MODO=='relat2') {
			$this->show_nodePathes();
			$this->show_cssParts();
			$this->show_xpathes();
			if ($MODO=='relat1')
				$this->show_extractionSumary(); 
			return ''; // já foi por print

		} elseif ($MODO=='raw') {
			return $this->saveXML();

		} elseif ($MODO=='xml')
			return $this->asXML($dayFilter);

		else {
			$xmlDom = $this->asStdXML('dom',$dayFilter);

			if ($MODO=='finalhtml') {
				return $this->asStdHtml('xml',$xmlDom, true, $dayFilter);

			} elseif ($MODO=='finalxml'){
				if ($finalUTF8)
					$xmlDom->encoding = 'UTF-8';
				return $xmlDom->saveXML();

			} else 
				die("\nERRO2: MODO $MODO DESCONHECIDO\n");
		}
	} // func

} // class



/////////  // APOIO XSL:

/**
 * Retorna parte desejada do ID de resumo.
 * @param $idx -1=sec+subsec, 1=sec, 2=subsec, 3=locid, 4=nome completo da sec.
 * Dependences: xsl_getSecao().
 */
function xsl_splitSecao($s,$idx) {
	if (preg_match('/^\s*([A-Z]+)\-?([a-z]?)([0-9]{1,5})\s*$/su',$s,$m)) {
		if ($idx>3)
			return xsl_getSecao($secid);
		elseif ($idx<0)
			return $m[1].$m[2];	
		else
			return $m[$idx];
	} else
		return $s;
}

function xsl_getSecao($secid) {
	global $SECAO;
	return isset($SECAO[$secid])? $SECAO[$secid]: '';
}


/**
 * Registra unique-string em array, para elementos válidos como string.
 * Uso em location e key.
 */
function xsl_nRegister($type,$pubid,$items) {
	// falta o ID no caso de keys
	global $registerLists;
	$tcExp = '';
	if ($type=='ev2') $tcExp = $type = 'loc';
	$secid = xsl_splitSecao($pubid,-1);
	$tsid = "$type#$secid";
    if (!isset($registerLists[$tsid]))
		$registerLists[$tsid]=array();
	foreach($items as $ele) {
		$tc=$ele->textContent;
		if ($tcExp)
			$tc = "(reunião de grupo 17:30h) $tc";
	    if (!isset($registerLists[$tsid][$tc]))
			$registerLists[$tsid][$tc]=array();
		$registerLists[$tsid][$tc][]=$pubid;
	}
    return NULL;//$ele;
}
/**
 * Restaura tag com itens registrados por xsl_nRegister().
 */
function xsl_regRestore($type,$secid){
	global $registerLists;
	$tsid = "$type#$secid";	
	$keys = array_unique( array_keys($registerLists[$tsid]) );
	usort($keys,'strcoll');
	$k= array();
	switch ($type) {
	case 'key': // keys
		foreach ($keys as $t) {
			$s = join(', ',$registerLists[$tsid][$t]);
			$k[] = "<key pubid-list=\"$s\">$t</key>";			
		}
		return DOMDocument::loadXML( '<keys>'.join('',$k).'</keys>' );
		break;
	case 'loc': // locations
		$s = join('</location><location>',$keys);
		return DOMDocument::loadXML( "<locations><location>$s</location></locations>" );
		break;
	default:
		return NULL;
	}
}

function xsl_dayFormat($s){
	if (preg_match('/(\d\d+)\-(\d+)\-(\d+)/',$s,$m))
		$s = "$m[3]/$m[2]/$m[1]";
    return DOMDocument::loadXML("<day>$s</day>");
}


function xsl_splitContrib($m) {
	$SEP = ',';
	$dom = new DOMDocument();
	$root = $dom->createElement('contrib-group');
	$txt = $m[0]->textContent;  // contribs não tem tags, ok txt!
	//foreach ($m[0]->childNodes as $node) $txt .= $node->nodeValue;
	$lst = explode($SEP,$txt);
	//for($i=count($lst)-1; $i+1; $i--) {
	$ni = count($lst) -1;
	for($i=0; $i<=$ni; $i++) {
		$name = trim($lst[$i]);
		$isCorresp = 0;
		$name = preg_replace('/\*$/','',$name,1,$isCorresp);
		$node = $dom->createElement('contrib'); // ,$name
		$node->setAttribute('contrib-type','author');

		if ($isCorresp)
			$node->setAttribute('corresp','yes');

		if (preg_match('/^(.+?)\s+(.+?)$/',$name,$m)) {
			$surname = str_replace('-','‑',$m[1]); //'&#8209;' copiado como  "―"
			$given =   str_replace('-','‑',$m[2]);
			$node->appendChild( $dom->createElement('surname',$surname) );
			//$node->appendChild( $dom->createTextNode(' ') ); // cuidado é NBSP dá pau! enquanto createTextNode('♣') funciona!
			// ver meu comentario em http://stackoverflow.com/a/8867502/287948 (mas resolvido por hora)
			$node->appendChild( $dom->createEntityReference('nbsp') );
			$node->appendChild( $dom->createElement('given-names',$given) ); // initials
		} else
			$node->appendChild( $dom->createElement('surname',$name) );

		$root->appendChild($node);
		if ($i<$ni) // not last 
			$root->appendChild( $dom->createTextNode("$SEP ") );	 // menos no last!
	}
    return $root; 
}

function xsl_markCorresp($m) {
	$txt = $m[0]->textContent;
	$txt = preg_replace_callback(
		'/([ :])([^@ :]+@[^ :;,]+)/us', 
		function ($s) {
			return "$s[1]<a href=\"$s[2]\">".strtolower($s[2]).'</a>';
		},   //old '$1<a href="$2">$2</a>', 
		$txt
	);
	return DOMDocument::loadXML("<corresp>$txt</corresp>");
}


/////////////// LIB ///////////////

function showDOMNode(DOMNode $domNode,$level=0) {
	$n=0;
    foreach ($domNode->childNodes as $node) {
    	$n++;
        print "\n\t [$level.$n] ".$node->nodeName.':'.$node->nodeValue;
        if($node->hasChildNodes()) {
        	print "\n ----";
            showDOMNode($node,$level+1);
        }
    }    
} // func


  function transformToDom($xsl, &$dom, $enforceUtf8=false) {
  	global $dayFilter; // da sec corrente
  	global $dayLocais; // idem

  	$xsldom = new DOMDocument('1.0','UTF-8');
  	if ((strlen($xsl) < 300) && (strpos($xsl,'<') === false))
  		$xsl = file_get_contents($xsl); // por hora nao precisa converter para UTF8, sempre estará!
	$xsldom->loadXML($xsl);
	$xsldom->encoding = 'UTF-8';
    $xproc = new XSLTProcessor();
    $xproc->registerPHPFunctions(); // custom
    $xproc->importStylesheet($xsldom);
    if ($dayFilter) {
    	preg_match('/\d+\-(\d+)\-(\d+)/', $dayFilter, $m);
    	$setDia = "$m[2]/$m[1]";
    	$xproc->setParameter('', 'dia', $setDia);
    	$xproc->setParameter('', 'local', $dayLocais);
    }
    return  $xproc->transformToDoc($dom);
    // return $this; 
  }


  function transformFrag_ToDom($xsl,&$dom) {
  	if ((strlen($xsl) < 300) && (strpos($xsl,'<') === false))
  		$xsl = file_get_contents($xsl);
	$s = '<?xml version="1.0" encoding="UTF-8"?>
			<xsl:stylesheet version="1.0"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:xlink="http://www.w3.org/1999/xlink"
				xmlns:mml="http://www.w3.org/1998/Math/MathML"
				xmlns:fn="http://php.net/xsl"
				exclude-result-prefixes="xlink mml fn"  
			>
		'.$xsl.'
		</xsl:stylesheet>
	';
    return transformToDom($s,$dom);
  }


  function transformId_ToDom($xsl,&$dom) {
  	if ((strlen($xsl) < 300) && (strpos($xsl,'<') === false))
  		$xsl = file_get_contents($xsl);
	$s = '<?xml version="1.0" encoding="UTF-8"?>
			<xsl:stylesheet version="1.0"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:xlink="http://www.w3.org/1999/xlink"
				xmlns:mml="http://www.w3.org/1998/Math/MathML"
				xmlns:fn="http://php.net/xsl"
				exclude-result-prefixes="xlink mml fn"  
			>
		<xsl:template match="@*|node()">
		  <xsl:copy>
		    <xsl:apply-templates select="@*|node()"/>
		  </xsl:copy>
		</xsl:template>
		'.$xsl.'
		</xsl:stylesheet>
	';
    return transformToDom($s,$dom);
  }

/// out

/**
 * Converte de UTF8 para entidades numéricas XML visíveis em navegadores HTML.
 * PS: dada a independencia com DTD, é preferivel à conversão para entidades não-numericas.
 * FALTA HOMOLOGAR com caracteres de ptBR_UTF8_desacentAll.
 * 
 * @param string $utf8_string texto txt-utf-8 a ser convertido;
 * @return string com caracteres especiais convertidos para entidades numéricas.
 */
function utf2html($utf8_string) {
    return mb_encode_numericentity ($utf8_string, array (160,  9999, 0, 0xffff), 'UTF-8');
}

?>