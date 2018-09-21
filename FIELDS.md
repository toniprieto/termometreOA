# Significado de los campos en los archivos de salida

## Campos archivo *_all.csv

| Campo  | Significado |
| ------------- | ------------- |
|File|Archivo procesado|
|Total|Número de publicaciones|
|NO DOI|Número de publicaciones sin DOI informado|
|OA JOURNAL|Número de publicaciones en revistas OA|
|OA JOURNAL (LOCAL IR)|Número de publicaciones en revistas OA disponibles en el repositorio local|
|OA JOURNAL (NO LOCAL IR)|Número de publicaciones en revistas OA **no** disponibles en el repositorio local|
|HYBRID JOURNAL|Número de publicaciones encontradas en una revista híbrida|
|HYBRID JOURNAL (LOCAL IR)|Número de publicaciones encontradas en una revista híbrida disponibles en el repositorio local|
|HYBRID JOURNAL (NO LOCAL IR)|Número de publicaciones encontradas en una revista híbrida **no** disponibles en el repositorio local|
|OA REPOSITORY|Número de publicaciones encontradas en un repositorio disponibles (y no en una revista OA o Híbrida)|
|OA REPOSITORY (LOCAL IR)|Número de publicaciones encontradas en un repositorio y disponibles en el repositorio local|
|OA REPOSITORY (NO LOCAL IR)|Número de publicaciones encontradas en un repositorio y **no** disponibles en el repositorio local|
|LOCAL IR ONLY|Número de publicaciones que solo se han encontrado en el repositorio local (puede ser errónea)|
|NO OA|Número de publicaciones no encontradas en abierto|
|% OA|Porcentage de publicaciones en abierto (no cuenta las publicaciones sin DOI)|

* Las publicaciones embargadas se contabilizan como documentos en abierto

## Campos archivo *_articles.csv

| Campo  | Significado |
| ------------- | ------------- |
|type|Clasificación de la publicación en función del tipo de versión en abierto/cerrado. Valores posibles: OPEN (revista OA), HYBRID (revista híbrida), REPOSITORY (en repositorio), CLOSED (sin acceso abierto), NODOI (sin doi informado)|
|OA en IR local|Indica si la publicación está en abierto en el repositorio local. NOIR (no está), OAIR (sí está)|
|doi|DOI informado|
|title|Título de la publicación|
|author|Autores de la publicación|
|OA JOURNAL (file)|Indica si en el archivo de entrada se indica como publciación en revista OA|
|OADOI OA|Clasificación de la publicación en base a la consulta a la API de oaDOI . Valores possibles: (ver primer campo)|
|OADOI evidence|Campo evidence de la mejor opción que devuelve la API de oaDOI|
|OADOI host type|Campo "host type" de la mejor opción que devuelve la API de oaDOI. Indica si es una revista OA, un editor o un repositorio |
|OADOI OA JOURNAL|Indica si oaDOI informa que está disponible en una revista OA|
|OADOI LOCAL IR|Indica si una de las fuentes que informa oaDOI en abierto corresponde al repositorio local|
|OADOI Num sources|Número de fuentes donde se encuentra una versión en abierto de la publicación en oaDOI|
|OPENAIRE OA|Clasificación de la publicación en base a la consulta a la API de OpenAire. Valores posibles: (ver primer campo)|
|OPENAIRE LOCAL IR|Indica si una de las fuentes que informa OpenAire en abierto corresponde al repositorio local|
|OPENAIRE Num sources|Número de fuentes donde se encuentra una versión en abierto de la publicación en OpenAire|
|LOCAL IR OA|Clasificación de la publicación en base a la consulta al OAI del repositorio local. Valores posibles: (ver primer campo)|
|LOCAL IR URL|Enlace al repositorio local|
|LOCAL IR DATE EMBARGO|Data de fin de embargo recuperada del repositorio local|
|CROSSREF LICENSE|Enlace a la licencia descargada de CrossRef|

