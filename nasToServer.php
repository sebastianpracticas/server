<?php

function readConfFile() {
	$handle = fopen("configurationRetrorevistas.conf", "r");
	if ($handle) {
		while (($line = fgets($handle)) !== false) {
			
			if (trim(explode(":", $line)[0]) == "sendTree") {
				if (trim(explode(":", $line)[1]) == "true") {
					fclose($handle);
					return true;
				}
			} 
		}
		fclose($handle);
	}
	return false;
}

function recopileTreeInformation() {
	$rootPath = "/var/www/pdf.retrorevistas/pdf-revistas";
	$root = scandir($rootPath);
	$arrayResult = [];
	foreach ($root as $dir) {
		if ($dir != "." && $dir != "..") {
			$subDir = scandir($rootPath."/".$dir);
			$arrayMagazine = [];
			foreach ($subDir as $issue) {
				if ($issue != "." && $issue != "..") {
					array_push($arrayMagazine, $issue);
				}
			}
			$arrayResult[$dir] = $arrayMagazine;
		}
	}
	return $arrayResult;
}

function generateLog($name, $first_line, $content = []) {
	$fileName = 'LOG_'.$name.'_'.date("Y-m-d_H:i:s").'.log';
	$handle = fopen("./log/$fileName","w");
	fwrite($handle, $first_line);
	foreach ($content as $keyLine => $arrayContent) {
		fwrite($handle, "\n$keyLine");
		foreach ($arrayContent as $contentLine) {
			fwrite($handle, "\n\t$contentLine");
		}
	}
	fclose($handle);
}

function sendInformation($content) {
	$url = "http://www.retrorevistas.com/dat/update";
	$data = array('data' => $content);
	$options = array(
		'http' => array(
			'header' => "content-type: application/x-www-form-urlencoded\r\n",
			'method' => 'POST',
			'content' => http_build_query($data)
		)
	);
	$context = stream_context_create($options);
	$result = file_get_contents($url, false, $context);
	if ($result === FALSE) {
		generateLog("NO_SEND", "Se ha intentado enviar un paquete, pero ha ocurrido un error o la respuesta del servidor web ha sido de error. Revise el log del servidor web.");
	}
}

while (true) {
	if (readConfFile()) {
		//Se ejecuta cuando la variable del fichero de configuración es "true" y envía el árbol de directorios completo.

		$recopiledTreeInformation = recopileTreeInformation();
		generateLog("SEND", "Se ha enviado un paquete con la siguiente información: ", $recopiledTreeInformation);
		sendInformation($recopiledTreeInformation);
		
	} else {
		//Se ejecuta cuando en el fichero de configuración sale "false" y no envía nada al servidor web.
		generateLog("NO_SEND", "Se ha intentado enviar un paquete, pero no se ha hecho debido a la configuración de 'configurationRetrorevistas.conf'.");
		
	}

	sleep(3600);	//Esperar una hora y volver a ejecutar
}

?>
