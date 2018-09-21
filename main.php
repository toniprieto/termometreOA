<?php
/**
 * Utilidad CLI para comprobar el estado de obertura
 * de un listado de publicaciones a través de su DOI
 * USO: php main.php -c config.php
 *
 * @author Toni Prieto
 * @date 2018-09-18
 */

require __DIR__ . '/vendor/autoload.php';

require_once("function.php");

//Definicion de los puntos de acceso de las APIs
// Ejemplo: https://api.unpaywall.org/v2/10.1038/nature12373?email=YOUR_EMAIL
define("API_OADOI","https://api.unpaywall.org/v2/");

//API de CrossRef
define("CROSSREF_API","https://api.crossref.org/v1/works/http://dx.doi.org/");

//API de OpenAire
define("OPENAIRE_API","http://api.openaire.eu/search/publications?format=json&doi=");

// Opciones de aplicación
$options=getopt('c:I');

if(@$options['c']) 
{
	// Archivo de configuració como parametro
	require_once($options['c']);
} 
else 
{
	die("Falta el parámetro c: \n" .
		"USO: php main.php -c config.php\n");
}

//Sanity checks
if (!isset($config["inputFolder"]) || !isset($config["outputFolder"]))
{
	die("Las carpetas de entrada o salida no estan configuradas. Entrada: " . $config["inputFolder"] . " Salida: " . $config["outputFolder"] ."\n");
}

if (!is_dir($config["inputFolder"])) {
    die("El directorio de entrada no existe\n");
}

if (!is_dir($config["outputFolder"])) {
    die("El directorio de salida no existe\n");
}

//Obtenemos los archivos de entrada
$csvFiles = glob($config["inputFolder"] . "/{*.csv,*.CSV,*.txt,*.TXT}",GLOB_BRACE);

//Sanity check: input folder has files
if (sizeof($csvFiles) == 0)
{
	die("No hay ficheros para procesar\n");
}

//Indices
$indexDOI = $config['indexDOI'];
$indexAuthor = $config['indexAuthor'];
$indexTitle = $config['indexTitle'];

$indexGOLD = null;
$valueGOLD = null;
if (isset($config['indexGOLD']))
{
	$indexGOLD = $config['indexGOLD'];
}
if (isset($config['valueGOLD']))
{
	$valueGOLD = $config['valueGOLD'];
}

//Para listados sacado del CRIS
$indexREPOurl = null;
$indexRights = null;
$valueRights = null;
if (isset($config['indexREPOurl']))
{
	$indexREPOurl = $config['indexREPOurl'];
}
if (isset($config['indexRights']))
{
	$indexRights = $config['indexRights'];
}
if (isset($config['valueRights']))
{
	$valueRights = $config['valueRights'];
}


//campos del csv de salida
$rowFields = array("type" => "type",
					"LOCALIRoa" => "OA en IR local",
					"doi" => "doi",
					"title" => "title",
					"author" => "author",
					"FILEjournalisoa" => "OA JOURNAL (file)",
					"OADOIstatus" => "OADOI OA",
					"OADOIevidence" => "OADOI evidence",
					"OADOIhosttype" => "OADOI host type",
					"OADOIjournalisoa" => "OADOI OA JOURNAL",
					"OADOIlocaldomain" => "OADOI LOCAL IR",
					"OADOIothersources" => "OADOI Num sources",
					"OPENAIREstatus" => "OPENAIRE OA",
					"OPENAIRElocaldomain" => "OPENAIRE LOCAL IR",
					"OPENAIREothersources" => "OPENAIRE Num sources",
					"LOCALIRstatus" => "LOCAL IR OA",
					"LOCALIRurl" => "LOCAL IR URL",
					"LOCALIRembargodate" => "LOCAL IR DATE EMBARGO",
					"CROSSREFlicenses" => "CROSSREF LICENSE"
					);
					
$fileFields = array("FILENAME" => "File",
					"TOTAL" => "Total",
					"NODOI" => "NO DOI",
					"GOLD" => "OA JOURNAL",
					"GOLD_OAIR" => "OA JOURNAL (LOCAL IR)",
					"GOLD_NOIR" => "OA JOURNAL (NO LOCAL IR)",
					"HYBRID" => "HYBRID JOURNAL",
					"HYBRID_OAIR" => "HYBRID JOURNAL (LOCAL IR)",
					"HYBRID_NOIR" => "HYBRID JOURNAL (NO LOCAL IR)",
					"REPOSITORY" => "OA REPOSITORY",
					"REPOSITORY_OAIR" => "OA REPOSITORY (LOCAL IR)",
					"REPOSITORY_NOIR" => "OA REPOSITORY (NO LOCAL IR)",
					"REPOSITORY_ONLY" => "LOCAL IR ONLY",
					"CLOSED" => "NO OA",
					"%OA" => "% OA"
					);

//Creamos el archivo de salida
$foAll = fopen($config["outputFolder"] . "/" . $config["sourceName"] . "_" . "All.csv",'w') or die("Error creant arxiu");
writeHeader($foAll,$fileFields);

//Processing xml files
foreach($csvFiles as $numFile => $filename)
{
	echo "***** Procesando " . ($numFile + 1) . ": " . $filename . " *****\n\n";
	
	if (($fd = fopen($filename, "r")) !== FALSE) 
	{
		//Crear archivo de salida para resultado individual
		$foArticles = fopen($config["outputFolder"] . "/" . $config["sourceName"] . "_" . basename($filename) . "_articles.csv",'w') or die("Error creatn arxiu");
		writeHeader($foArticles,$rowFields);
		
		//Inicailizar el array de valores
		$fileResult = array();
		foreach($fileFields as $id => $name)
		{
			$fileResult[$id] = 0;
		}

		$numRecord = 1;

		$hasHeader = true;

		if (isset($config["hasHeader"])) {
		    $hasHeader = $config["hasHeader"];
        }

		while (($data = fgetcsv($fd, 100000, ",")) !== FALSE) 
		{
			$rowResult = array();

			if (!$hasHeader || $numRecord > 1) {

                $doi = $data[$indexDOI];

                $rowResult['doi'] = $doi;
                $rowResult['title'] = $data[$indexTitle];
                $rowResult['author'] = $data[$indexAuthor];

                if (isset($indexGOLD) && isset($goldValue)) {
                    if ($goldValue === $data[$indexGOLD]) {
                        $rowResult["FILEjournalisoa"] = true;
                    }
                }

                //Si se trata de un DOI
                if (isDOI($doi)) {
                    if ($config['email'] == null || $config['email'] == "") {
                        die("Indica un correo en la configuración para la consulta a la API de oaDoi\n");
                    }

                    //OADOI REQUEST
                    $url = API_OADOI . $doi . "?email=" . $config['email'];
                    $obj = getJSONresponse($url);

                    if (isset($obj->is_oa)) {
                        if ($obj->is_oa) {
                            $rowResult['OADOIstatus'] = "OPEN";
                        } else {
                            $rowResult['OADOIstatus'] = "CLOSED";
                        }

                        // campo "evidence", marca el origen de la mejor version oa
                        // (best oa location)
                        if (isset($obj->best_oa_location->evidence)) {
                            $rowResult['OADOIevidence'] = $obj->best_oa_location->evidence;
                        }

                        //si esta en host_type = pubisher es de tipo "hybrid journal"
                        if (isset($obj->best_oa_location->host_type)) {
                            $rowResult['OADOIhosttype'] = $obj->best_oa_location->host_type;
                        }

                        //journal is OA
                        if (isset($obj->journal_is_oa)) {
                            $rowResult['OADOIjournalisoa'] = $obj->journal_is_oa;
                        }

                        //miramos el dominio de las versiones abiertas para comprobar
                        //si la del repositorio es la única
                        $rowResult['OADOIlocaldomain'] = "0";
                        $rowResult['OADOIothersources'] = 0;
                        if (isset($obj->oa_locations)) {
                            foreach ($obj->oa_locations as $oalocation) {
                                if (containsDomain($oalocation->url, $config['localdomain'])) {
                                    $rowResult['OADOIlocaldomain'] = true;
                                } else {
                                    $rowResult['OADOIothersources']++;
                                }
                            }
                        }
                    } else {
                        $rowResult['OADOIstatus'] = "UNKNOWN";
                    }
                    //END OADOI

                    //CROSSREF REQUEST
                    if ($config['crossrefRequest']) {
                        //from crossref we can get a license
                        $url = CROSSREF_API . $doi;
                        $obj = getJSONresponse($url);

                        $licensesurls = "";
                        if (isset($obj->message->license)) {

                            foreach ($obj->message->license as $license) {
                                if (!strcmp($licensesurls, "")) {
                                    $licensesurls .= $license->URL;
                                } else {
                                    $licensesurls .= ";" . $license->URL;
                                }
                            }
                        }
                        $rowResult['CROSSREFlicenses'] = $licensesurls;
                    }
                    //END CROSSREF

                    //OpenAire REQUEST
                    $url = OPENAIRE_API . $doi;
                    $obj = getJSONresponse($url);

                    //FIXME: accedemos al result[0], aunque siendo un doi, no deberia dar más resultados,
                    // quizás pasa en algun caso
                    if (isset($obj->response->results->result[0]->metadata->{"oaf:entity"}->{"oaf:result"}->bestaccessright->{"@classid"})) {
                        // devuelve OPEN, EMBARGO, CLOSED, ...
                        $rowResult['OPENAIREstatus'] = $obj->response->results->result[0]->metadata->{"oaf:entity"}->{"oaf:result"}->bestaccessright->{"@classid"};

                        //miramos el dominio de las versiones abiertas para comprobar
                        //si la del repositorio es la única
                        $rowResult['OPENAIRElocaldomain'] = false;
                        $rowResult['OPENAIREothersources'] = 0;
                        if (isset($obj->response->results->result[0]->metadata->{"oaf:entity"}->{"oaf:result"}->children->instance)) {
                            $instances = $obj->response->results->result[0]->metadata->{"oaf:entity"}->{"oaf:result"}->children->instance;

                            // puede ser una array
                            if (is_array($instances)) {
                                $instancesarray = $instances;
                            } // si es un unico valor, creamos una array
                            else {
                                $instancesarray = array();
                                $instancesarray[] = $instances;
                            }

                            foreach ($instancesarray as $num => $instance) {
                                //Antes se llamaba $intance->licence!
                                $license = $instance->accessright->{"@classid"};
                                if ($license == 'OPEN' || $license == 'EMBARGO') {
                                    // si hay mas de un recurso web
                                    // concatemnaoms los resultads (luego comprovaremos si alguno contiene una dirección local)
                                    // (podria fallar)
                                    if (is_array($instance->webresource)) {
                                        $url = "";
                                        foreach ($instance->webresource as $num => $wr) {
                                            $url .= $wr->url->{"$"};
                                        }
                                    } else {
                                        $url = $instance->webresource->url->{"$"};
                                    }

                                    // dominio local presente
                                    if (containsDomain($url, $config['localdomain'])) {
                                        $rowResult['OPENAIRElocaldomain'] = true;
                                    } else {
                                        $rowResult['OPENAIREothersources']++;
                                    }
                                }
                            }
                        }
                    } else {
                        $rowResult['OPENAIREstatus'] = "NOTFOUND";
                    }
                    //END OpenAire

                    //Local repository REQUEST
                    $response = getLocalInfo(array('doi' => $doi));
                    $rowResult['LOCALIRstatus'] = $response['status'];
                    $rowResult['LOCALIRurl'] = $response['url'];
                    $rowResult['LOCALIRembargodate'] = $response['embargodate'];
                    //END local repository

                }

                //Part especifica CRIS (UPC-DRAC)

                // Amb les dades extretes de DRAC podem obtenir el handle de UPCommons vinculat
                // això es permet recuperar informació sense necessitat de disposar del DOI
                // a més podem extreure un camp que indiqui si està en accés obert (correspon a indexRights)
                $localInfo = false;
                if (isset($indexREPOurl) && isset($indexRights) && isset($valueRights)) {
                    //Si coincideixen els valors esta en OA al repositori
                    if (!strcmp($data[$indexRights], $valueRights)) {
                        $rowResult['LOCALIRstatus'] = "OPEN";
                        $rowResult['LOCALIRurl'] = getLocalUrlValue($data[$indexREPOurl], $config['localdomain']);
                        $localInfo = true;
                    } else {
                        //sino, consultem per l'ID
                        $urlsValue = $data[$indexREPOurl];

                        // devuelve la url local en el campo, si existe
                        $url = getLocalUrlValue($data[$indexREPOurl], $config['localdomain']);

                        if ($url != null) {
                            $response = getLocalInfo(array('url' => $url));
                            $rowResult['LOCALIRstatus'] = $response['status'];
                            $rowResult['LOCALIRurl'] = $response['url'];
                            $rowResult['LOCALIRembargodate'] = $response['embargodate'];
                            $localInfo = true;
                        }
                    }

                    if (!isset($rowResult["OADOIstatus"])) $rowResult["OADOIstatus"] = "UNKNOWN";
                    if (!isset($rowResult["OPENAIREstatus"])) $rowResult["OPENAIREstatus"] = "UNKNOWN";
                }
                //END Part especifica CRIS (DRAC)

                // *** PART 2 ***

                // Clasifica y cuenta los resultados

                // 1) Tipo de publicación encontrada:
                //		(a) Tipo obertura: NODOI, GOLD, HYBRID, REPOSITORY, CLOSED,
                //		(b) En el repositorio?: OAIR, NOIR
                if (!isDOI($doi) && !$localInfo) {
                    $rowResult["type"] = "NODOI";
                    $rowResult["LOCALIRoa"] = "UNKNOWN";
                } else {
                    if (isset($rowResult["FILEjournalisoa"]) && $rowResult["FILEjournalisoa"] === true) {
                        $rowResult["type"] = "GOLD";
                    } else if (isset($rowResult["OADOIjournalisoa"]) && $rowResult["OADOIjournalisoa"] === true) {
                        $rowResult["type"] = "GOLD";
                    } else if (isset($rowResult["OADOIhosttype"]) && $rowResult["OADOIhosttype"] === "publisher") {
                        $rowResult["type"] = "HYBRID";
                    } else {
                        // No es ni revista OA ni hibrida
                        // consultamos por otras opciones en abierto (repositorio)
                        if ($rowResult["OADOIstatus"] === "OPEN" || $rowResult["OPENAIREstatus"] === "OPEN" || $rowResult["LOCALIRstatus"] === "OPEN") {
                            $rowResult["type"] = "REPOSITORY";
                        } // Embargo lo contamos como abierto también
                        else if ($rowResult["OADOIstatus"] === "EMBARGO" || $rowResult["OPENAIREstatus"] === "EMBARGO" || $rowResult["LOCALIRstatus"] === "EMBARGO") {
                            $rowResult["type"] = "REPOSITORY";
                        } else {
                            $rowResult["type"] = "CLOSED";
                        }
                    }

                    if ($rowResult["LOCALIRstatus"] === "OPEN" || $rowResult["LOCALIRstatus"] === "EMBARGO") {
                        $rowResult["LOCALIRoa"] = "OAIR";
                    } else {
                        $rowResult["LOCALIRoa"] = "NOIR";
                    }

                }

                //2) Contar los resultados
                $fileResult["TOTAL"]++;
                $fileResult[$rowResult["type"]]++;
                if ($rowResult["type"] !== "NODOI" && $rowResult["type"] !== "CLOSED") {
                    $fileResult[$rowResult["type"] . "_" . $rowResult["LOCALIRoa"]]++;

                    // Caso especial: el IR local es la única fuente?
                    if (!strcmp("REPOSITORY_OAIR", $rowResult["type"] . "_" . $rowResult["LOCALIRoa"])) {
                        if (!isset($rowResult["OADOIothersources"])) $rowResult["OADOIothersources"] = 0;
                        if (!isset($rowResult["OPENAIREothersources"])) $rowResult["OPENAIREothersources"] = 0;

                        if (($rowResult["OADOIothersources"] + $rowResult["OPENAIREothersources"]) == 0) {
                            $fileResult["REPOSITORY_ONLY"]++;
                        }
                    }
                }

                writeValues($foArticles, $rowFields, $rowResult);

                if ($config['verbose']) {
                    echo "*** Resultado obtenido registro " . $fileResult["TOTAL"] . " con DOI (" . $doi . ")\n";
                    print_r($rowResult);
                    echo "\n\n";
                }
            }

            $numRecord++;
	
		} //end while read lines
		
		// calculamos el porcentage OA
		$fileResult["%OA"] = ($fileResult["GOLD"] + $fileResult["HYBRID"] + $fileResult["REPOSITORY"]) / ($fileResult["TOTAL"] - $fileResult["NODOI"]); 
		
		// Guardamos el resultado
		$fileResult["FILENAME"] = basename($filename);
		writeValues($foAll,$fileFields,$fileResult);
		fclose($foArticles);
	}
}
fclose($foAll);	
?>
