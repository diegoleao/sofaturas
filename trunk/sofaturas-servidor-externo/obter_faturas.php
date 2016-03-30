<?php

error_reporting(0);
ini_set('display_errors', 0);

$db_connection = mysql_connect( "mysql24.gameblox.com.br", "gameblox23", "Blox1243" ) or die( mysql_error() );
mysql_select_db( "gameblox23" ) or die( mysql_error() );

logAction(json_encode($_POST), "OK - INICIOU");
 
$chavesDinamicas = array_keys($_POST);

$query_tail = " Vencimento BETWEEN '" . $_POST["dataInicial"] . "' AND '" . $_POST["dataFinal"] . "'";

for ($i=2; $i < count($_POST); $i++) 
{
	// os dois primeiros parametros do array POST são a data inicial e final ja tratados anteriormente
	//o restante são os parametros validadores em si	
	$query_tail .= " AND ParametrosValidadores LIKE '%".$chavesDinamicas[$i]."=[".$_POST[$chavesDinamicas[$i]]."]%'";
}

$fullstring = "SELECT * FROM EXT_SIMULADO_FATURAS WHERE" . $query_tail . ";";
//file_put_contents("debug_externo.txt", "\n\n\n\n\n\n<<<<<NEW ENTRY>>>>>\n\n". $fullstring);

$faturas = array();
$query = mysql_query($fullstring);
$faturasObtidas = mysql_fetch_array($query);
if(mysql_error())
{
	logAction("---", "ERRO - " . mysql_error());
	echo json_encode(array("status_externo" => 0, "debug" => "falha de SQL", "msg" => "Falha na comunicação com o servidor externo"));
}
else if ($faturasObtidas == NULL)
{
    logAction("---", "WARNING - NADA ENCONTRADO");
	echo json_encode(array("status_externo" => 1, "debug" => "nenhuma fatura retornada", "msg" => "Não foram encontradas faturas com os dados fornecidos"));
}
else
{	
	do
	{
	    //$detalhesEncoded = json_decode($faturasObtidas[7]);
        //$detalhesEncoded = objectToArray($detalhesEncoded);
		$faturas[] = array("idExterno" => $faturasObtidas[0], "valor" => $faturasObtidas[3], "vencimento" => $faturasObtidas[4], "emissao" => $faturasObtidas[5], "codigoBarras" => $faturasObtidas[6], "detalhes" => $faturasObtidas[7], "params" => $faturasObtidas[2]);
	} 
	while(($faturasObtidas = mysql_fetch_array($query, MYSQL_NUM)) != NULL);
	logAction("---", "OK - RESULTADO: " . json_encode($faturas));
	echo json_encode($faturas);
}

function objectToArray($d) 
{
    if (is_object($d)) {
        // Gets the properties of the given object
        // with get_object_vars function
        $d = get_object_vars($d);
    }

    if (is_array($d)) {
        /*
        * Return array converted to object
        * Using __FUNCTION__ (Magic constant)
        * for recursive call
        */
        return array_map(__FUNCTION__, $d);
    } else {
        // Return array
        return $d;
    }
}

function logAction($entrada, $resultado) {
    file_put_contents("insert_log.txt", "\nlogAction_START", FILE_APPEND);
    $insert_query = "INSERT INTO LOG_EXTERNO (Entrada, Resultado, Data) 
    VALUES ('".$entrada."', '".$resultado."', NOW());";
    $query_return = mysql_query($insert_query);
    if($query_return)
    {
        file_put_contents("insert_log.txt", "\nOK", FILE_APPEND);
        return 1;
    } 
    else
    {
        file_put_contents("insert_log.txt", "\nNOT_OK", FILE_APPEND);
        return 0;
    }
}
?>