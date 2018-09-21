<?php
/**
 * Funciones auxiliares
 *
 * @author Toni Prieto
 * @date 2018-09-18
 */


//Devuelve el JSON descodificado del resultado
// de llamar a la url
function getJSONresponse($url)
{
    try {
        $client = new \GuzzleHttp\Client();
        // hacemos las peticiones con un delay de 1/2 segundo
        $res = $client->request('GET', $url, ["delay" => 500]);
        if ($res->getStatusCode() == 200) {
            return json_decode($res->getBody());
        } else {
            return array();
        }
    } catch(\GuzzleHttp\Exception\ClientException $e) {

        //echo "Se ha producido un error en la petición a la API: " . $e->getMessage() . "\n";

        return array();
    }
}

//Variables globales para guardar los datos del fichero
//descargados por OAI
$initRepoList = false;
$repoListDois = array();
$repoListUrls = array();

//Para mejorar el rendimiento se peiude
function getLocalInfo($values)
{
    global $initRepoList, $repoListDois, $repoListUrls;

    if (!$initRepoList) {
        loadOAIFile();
    }

    if (isset($values['doi']))
    {
        if (isset($repoListDois[$values['doi']])) {
            return $repoListDois[$values['doi']];
        }
    }
    else if (isset($values['url']))
    {
        if (isset($repoListUrls[$values['url']])) {
            return $repoListUrls[$values['url']];
        }
    }

    //Sino devolemos valor no encontrado
    $response["status"] = "NOTFOUND";
    $response["url"] = "";
    $response["embargodate"] = "";

    return $response;
}

function loadOAIFile()
{
    global $initRepoList, $repoListDois, $repoListUrls;

    echo "Cargando lista de dois y urls descargadas del repositorio...\n";

    //Abrimos el archivo con los dois/urls descargadas
    if (file_exists("./dois-repositorio.txt")) {
        if (($file = fopen("./dois-repositorio.txt", "r")) !== FALSE) {
            while (($datos = fgetcsv($file, 1000, "\t")) !== FALSE) {
                //0 => doi
                $doi = $datos[0];
                //1 => url
                $urls = $datos[1];
                //2 => rights
                $rights = $datos[2];
                //3 => embargoDate
                $embargoDate = $datos[3];

                $urlArray = explode(";", $urls);

                //Guardamos el doi obtenido
                if ($doi != "") {
                    if (!isset($repoListDois[$doi])) {
                        $repoListDois[$doi] = array("status" => $rights, "url" => $urls, "embargodate" => $embargoDate);
                    }
                }

                //Guardamos todas la urls obtenidas
                foreach ($urlArray as $num => $url) {
                    if (!isset($repoListUrls[$url])) {
                        $repoListUrls[$url] = array("status" => $rights, "url" => $url, "embargodate" => $embargoDate);
                    }
                }
            }
            fclose($file);
        }

        echo "\t" . sizeof($repoListDois) . " DOIs cargadas\n";
        echo "\t" . sizeof($repoListUrls) . " URLs cargadas\n";
        echo "\n";
    }
    else
    {
        echo "\nATENCIÓN! El archivo dois-repositorio.txt no existe. Calculando sin datos del repositorio local\n\n";
    }

    $initRepoList = true;
}


// Devuelve true si el segundo parametro (array de Strings)
// contiene el valor del primer parametro (String)
function containsDomain($url,$domains)
{
	if (!isset($domains)) return false;
	
	if (!is_array($domains))
	{
		if(stripos($url,$domains) > 0) return true;
	}
	else
	{
		foreach($domains as $num => $domain)
		{
			if(stripos($url,$domain) > 0) return true;
		}
	}

	return false;
}


// Devuelve la primera url que contiene algun dominia del segundo parametro
// $urlValues: lista de urls separadas por comas
// $domains: String o Array 
function getLocalUrlValue($urlsValue,$domains)
{
	if (!isset($domains)) return null;

	$urls = explode(",",$urlsValue);

	foreach($urls as $num => $url)
	{
		if (!is_array($domains))
		{
			if(stripos($url,$domains) > 0) return $url;
		}
		else
		{
			foreach($domains as $num => $domain)
			{
				if(stripos($url,$domain) > 0) return $url;
			}
		}
	}	
	
	return null;
}

// Devuelve true si empieza por 10.
function isDOI($doi)
{
	if ($doi != null && (substr($doi, 0, strlen("10.")) === "10."))
		return true;
	else
		return false;
}

// Devuelve true si se trata de un doi en sus diferentes formas
function isDOIExtended($doi)
{
	if (startsWith($doi,"http://doi.org/")) {
		$doi = str_replace("http://doi.org/","",$doi);
	} else if (startsWith($doi,"https://doi.org/")) {
        $doi = str_replace("https://doi.org/","",$doi);
	} else if (startsWith($doi,"http://doi.org/")) {
        $doi = str_replace("http://dx.doi.org/","",$doi);
    } else if (startsWith($doi,"https://dx.doi.org/")) {
        $doi = str_replace("https://dx.doi.org/","",$doi);
    } else if (startsWith($doi,"info:eu-repo/semantics/altIdentifier/doi/")) {
        $doi = str_replace("info:eu-repo/semantics/altIdentifier/doi/","",$doi);
	}

    if ($doi != null && (substr($doi, 0, strlen("10.")) === "10."))
        return true;
    else
        return false;
}

// normaliza el valor de un doi
// pasando url o identificador openaire a doi simple
function getDOIValue($doi)
{

    if (startsWith($doi,"http://doi.org/")) {
        $doi = str_replace("http://doi.org/","",$doi);
    } else if (startsWith($doi,"https://doi.org/")) {
        $doi = str_replace("https://doi.org/","",$doi);
    } else if (startsWith($doi,"http://dx.doi.org/")) {
        $doi = str_replace("http://dx.doi.org/","",$doi);
    } else if (startsWith($doi,"https://dx.doi.org/")) {
        $doi = str_replace("https://dx.doi.org/","",$doi);
    } else if (startsWith($doi,"info:eu-repo/semantics/altIdentifier/doi/")) {
        $doi = str_replace("info:eu-repo/semantics/altIdentifier/doi/","",$doi);
    }

    return $doi;
}

// escribe los valores del csv
function writeValues(&$file,$headers,$values)
{
	$row = array();
	foreach($headers as $id => $name)
	{
		if (isset($values[$id])) {
            if ($values[$id] === false) {
                $row[] = "0";
            } else {
                $row[] = $values[$id];
            }
        } else {
            $row[] = "-";
        }
	}
	
	fputcsv($file,$row,',','"');
}

// escribe la cabecera del csv
function writeHeader(&$file, $headers)
{
	$row = array();
	foreach($headers as $id => $name)
	{
		$row[] = $name;
	}
	
	fputcsv($file,$row,',','"');
}

//función auxiliar
function startsWith($haystack, $needle)
{
     $length = strlen($needle);
     return (substr($haystack, 0, $length) === $needle);
}

//función auxiliar
function endsWith($haystack, $needle)
{
    $length = strlen($needle);

    return $length === 0 || 
    (substr($haystack, -$length) === $needle);
}

?>
