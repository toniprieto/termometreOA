# Termòmetre OA

Scripts PHP para consultar el grado de apertura de un listado de publicaciones a partir de su DOI.

A partir de un CSV con un listado de publicaciones consulta si hay versiones en abierto de la publicación y si se trata de revistas OA mediante consultas a las API de oaDOI, OpenAire y al repositorio local y genera una tabla que indica el grado de apertura entre otros datos.

## Instalación

Descarga el código en una carpeta local.

Descarga las librerías externas mediante [composer](https://getcomposer.org/download/) ejecuta desde la carpeta:

```
$ php composer.phar install
```

## Preparación

Para utilizarlo:

1. Crea uno o varios archivos CSV distribuidos por año que contengan un listado de publicaciones con DOI, título y autores y guarda estos archivos en una carpeta de entrada. Los CSVs deben estar separados por comas y usar doble comillas como delimitador.

2. Personaliza el archivo config.php indicando en que columna se encuentra cada campo (consulta el archivo para más opciones)

## Ejecución

### Paso previo (descargar información OAI local)

Para utilizar la información sobre las publicaciones disponibles en el repositorio local es necesario lanzar primero el script load-doi-from-repository.php indicando la url base del servidor OAI para descargar el listado de publicaciones con su doi, url y condiciones de acceso y isa generar un archivo local que se utilizará luego.

```
$ php load-doi-from-repository.php -o <oai_endpoint>
```

El servidor OAI debe ser compatible con OpenAire y tener normalizado los valores con las condiciones de acceso.

### Programa principal

Finalmente, el programa principal se lanza con:

```
$ php main.php -c config.php
```

## Resultado

Al finalizar el proceso crea en la carpeta de salida configurada diferentes archivos:

* Archivos <nombre_archivo>_articles.csv con el resultado de cada publicación consultada

* Archivo <nombre_archivo>_all.csv con la tabla resumen de los datos procesados (una fila por archivo)

[Ver tabla con los campos disponibles en cada archivo](FIELDS.txt)
