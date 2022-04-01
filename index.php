<?php
/*
function crossDomainHeader() {
    if (!isset($_SERVER['HTTP_ORIGIN'])) {
        // This is not cross-domain request
        exit;
    }

    $wildcard = FALSE; // Set $wildcard to TRUE if you do not plan to check or limit the domains
    $credentials = FALSE; // Set $credentials to TRUE if expects credential requests (Cookies, Authentication, SSL certificates)
    
    //$allowedOrigins = array('');    //AÑADIR DOMINIO WEBRETROREVISTAS.
    //if (!in_array($_SERVER['HTTP_ORIGIN'], $allowedOrigins) && !$wildcard) {
        // Origin is not allowed
        //exit;
    //}
    
    $origin = $wildcard && !$credentials ? '*' : $_SERVER['HTTP_ORIGIN'];

    header("Access-Control-Allow-Origin: " . $origin);
    if ($credentials) {
        header("Access-Control-Allow-Credentials: true");
    }
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Origin");
    header('P3P: CP="CAO PSA OUR"'); // Makes IE to support cookies

    // Handling the Preflight
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') { 
        exit;
    }

    // Response
    header("Content-Type: application/json; charset=utf-8");
}
*/
function createShortcut($PdfPath, $publicPath) {
    
    $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $shortcutName = substr(str_shuffle($permitted_chars), 0, 16);
    
    // FOR LINUX:
    symlink("/home/sebas/Descargas/principito.pdf", "$publicPath/$shortcutName");

    /* FOR WINDOWS:
    $com = new COM('WScript.Shell');
    $shortcut = $com->CreateShortcut("./pdf.retrorevistas/public/$shortcutName.lnk");
    $shortcut->TargetPath = "./$PdfPath";
    $shortcut->Save();
    $com = null;
    */

    return $shortcutName;
}

function deleteShortcuts($publicPath) {

    $currentTime = time();
    $secondsOld = 43200;	//12 horas

    $directory = scandir("$publicPath");
    foreach ($directory as $fileName) {
	$filePath = "$publicPath/$fileName";
	if (file_exists($filePath) && is_file($filePath)) {
	    if ($currentTime > (lstat($filePath)['atime'] + $secondsOld)) {
		unlink($filePath);
	    }
	}
    }
}

//GET PARAMS VIA POST:
if (!isset($_POST["token"])) {
 $pdo = new PDO('sqlite:tokens.db');
 $statement = $pdo->exec("INSERT INTO tokens (token, path) VALUES ('9999', './')");
    exit;
}

$token = $_POST["token"];

//DB CONNECTION
$pdo = new PDO('sqlite:tokens.db');

if (isset($_POST["path"])) {
//Private connection: Server -> NAS.

	$path = $_POST["path"];

	//Consulta sqlite
	$statement = $pdo->exec("INSERT INTO tokens (token, path) VALUES ('$token', '$path')");
	echo json_encode($statement);
} else {
//Public Connection: Client -> NAS.
	//HEADERS:
	//crossDomainHeader();

	//SET PUBLIC PATH:
	$publicPath = "/var/www/pdf.retrorevistas/public";

	//RESPONSE VARIABLE:
	$arrayResponse = array("exists"=>0);

	//Consulta sqlite
	$statement = $pdo->query("SELECT * FROM tokens WHERE token = $token;");
	$rows = $statement->fetchAll(PDO::FETCH_ASSOC);
	if (count($rows) > 0) {
	    $arrayResponse["exists"] = 1;
	    $arrayResponse["accessName"] = createShortcut($rows[0]["path"], $publicPath);
	    $pdo->exec("DELETE FROM tokens WHERE token = $token;");
	}
	deleteShortcuts($publicPath);//Delete shotcuts with more of 24 hours.
	echo json_encode($arrayResponse);
}
