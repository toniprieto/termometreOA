<?php
/**
 * Script para recuperar doi, url y condiciones de acceso de
 * los registros de un repositorio compatible con las directrices
 * de OpenAire a través de su servidor OAI
 *
 * Genera un archivo en dois-repositorio.txt con la información
 * descargada
 *
 * @author Toni Prieto
 * @date 2018-09-18
 */

require __DIR__ . '/vendor/autoload.php';

require_once("function.php");

// Opciones de aplicación
$options=getopt('o:I');

if(@$options['o']) 
{
	// Archivo de configuració como parametro
	// http://example.com/oai/openaire
	$oaiendpoint = $options['o'];
} 
else 
{
	die("Falta el parámetro o: \n" .
		"USO: php load-doi-from-repositori.php -o <oai_endpoint>\n");
}

// Muestra más información
$verbose = true;

// Incluir registros sin doi
$includeAll = true;

//Creamos l'arxiu
$file = fopen("./dois-repositorio.txt", "w");


$client = new \Phpoaipmh\Client($oaiendpoint);
$myEndpoint = new \Phpoaipmh\Endpoint($client);

echo "Obteniendo registros del servidor OAI: " . $oaiendpoint . "\n";

try {

    // Recs will be an iterator of SimpleXMLElement objects
    $recs = $myEndpoint->listRecords("oai_dc", null, null, null);

    // The iterator will continue retrieving items across multiple HTTP requests.
    // You can keep running this loop through the *entire* collection you
    // are harvesting.  All OAI-PMH and HTTP pagination logic is hidden neatly
    // behind the iterator API.

    $num = 0;
    foreach ($recs as $rec) {

        if ($verbose) {
            echo "Procesando registro " . ($num + 1) . " (ID: " . $rec->header->identifier . ")\n";
        }

        $deleted = false;
        if (isset($rec->header->attributes()["status"]) && (string)$rec->header->attributes()["status"] == "deleted") {
            $deleted = true;
        } else {
            $deleted = false;
        }

        if (!$deleted) {
            $data = $rec->metadata->children('http://www.openarchives.org/OAI/2.0/oai_dc/');
            $rows = $data->children('http://purl.org/dc/elements/1.1/');

            $doi = null;
            $identifiers = array();
            $rights = null;
            $embargodate = null;

            foreach ($rows as $dc => $value) {

                //echo $dc . ":" . $value . "\n";

                if (!strcmp($dc, "identifier")) {
                    if (isDoiExtended($value)) {
                        $doi = getDOIValue($value);
                    } else {
                        if (startsWith($value,"http")) {
                            $identifiers[] = $value;
                        }
                    }
                }

                if (!strcmp($dc, "rights")) {
                    if (startsWith($value, "info:eu-repo/semantics/")) {
                        if (!strcmp($value, "info:eu-repo/semantics/closedAccess")) {
                            $rights = "CLOSED";
                        } else if (!strcmp($value, "info:eu-repo/semantics/restrictedAccess")) {
                            $rights = "CLOSED";
                        } else if (!strcmp($value, "info:eu-repo/semantics/embargoedAccess")) {
                            $rights = "EMBARGO";
                        } else if (!strcmp($value, "info:eu-repo/semantics/openAccess")) {
                            $rights = "OPEN";
                        }
                    }
                }

                if (!strcmp($dc, "date")) {
                    if (startsWith($value, "info:eu-repo/semantics/embargoedAccess")) {
                        $embargodate = str_replace("info:eu-repo/semantics/embargoedAccess", "", $value);
                    }
                }
            }

            //Escribir resultado en el fichero
            if ($includeAll) {
                fwrite($file,$doi . "\t" . implode(";", $identifiers) . "\t" . $rights . "\t" . $embargodate . "\n");
            }
            else if ($doi != null) {
               if ($verbose) {
                   echo "\t" . "DOI encontrado: " . $doi . "\n";
               }

               fwrite($file,$doi . "\t" . implode(";", $identifiers) . "\t" . $rights . "\t" . $embargodate . "\n");
            }

        }

        $num++;
    }

    echo "Creado archivo local dois-repositorio.txt con los datos de las publicaciones descargades del OAI\n";

} catch (\Phpoaipmh\Exception\OaipmhException $exception) {

    if ($exception->getOaiErrorCode() == "noRecordsMatch") {
        echo "\nRESULTADO: La consulta no ha devuelto registros\n";
    } else {
        echo "Se ha producido un error descargando los registros del servidor OAI\n";
        echo $exception->getMessage() . "\n";
    }
}
finally
{
    fclose($file);
}


?>
