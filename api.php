<?php
//ini_set('precision', 3);
ini_set('serialize_precision', 3);
header('Access-Control-Allow-Origin: *');
header('Content-type: application/json; charset=utf-8');

$_GET['token'] = $_GET['token']?? 'NULL';
$bd = new SQLite3('bd.sqlite3', SQLITE3_OPEN_READWRITE);

// Comprobación de token
$token = $bd->query("SELECT COUNT(*) AS 'token' FROM usuarios WHERE token = '".$_GET['token']."'");
$token->fetchArray(SQLITE3_NUM)[0]?: exit(header('HTTP/1.0 401'));

// Comprobamos si se solicitó actualización/inserción de datos con parámetro "put"/"post" en QS

if(isset($_GET['put'])){
    $_GET['cod']?? exit('{"error":"Falta parametro cod"}');

    $set = array();
    isset($_GET['precio'])? ($set[] = 'precio = '.(int)$_GET['precio']) : null;
    isset($_GET['disponible'])? ($set[] = 'disponible = '.($_GET['disponible']? 1 : 0)) : null;

    $sql = "UPDATE productos SET " . implode(' , ', $set) . " WHERE cod = '".addslashes($_GET['cod'])."'";

    $bd->query($sql)?: exit('{"error":"Error escribiendo cambios en la base de datos"}');
}

if(isset($_GET['post'])){
    // validaciones de variables
    $_GET['cod']?? exit('{"error":"Falta parametro cod."}'.header('HTTP/1.0 400'));
    $_GET['nombre']?? exit('{"error":"Falta parametro nombre."}'.header('HTTP/1.0 400'));
    $_GET['precio']?? exit('{"error":"Falta parametro precio."}'.header('HTTP/1.0 400'));
    $_GET['disponible']?? exit('{"error":"Falta parametro disponible."}'.header('HTTP/1.0 400'));

    // validaciones de datos
    ($_GET['cod']==='')? exit('{"error":"Falta parametro cod."}'.header('HTTP/1.0 400')) : null;
    ($_GET['nombre']==='')? exit('{"error":"Falta parametro nombre."}'.header('HTTP/1.0 400')) : null;
    (!is_numeric($_GET['precio']))? exit('{"error":"Falta parametro precio."}'.header('HTTP/1.0 400')) : null;
    (!is_numeric($_GET['disponible']))? exit('{"error":"Falta parametro disponible."}'.header('HTTP/1.0 400')) : null;

    $sql = "INSERT INTO productos
    (cod, nombre, precio, disponible)
    VALUES 
        (
            '".addslashes($_GET['cod'])."',
            '".addslashes($_GET['nombre'])."', 
            ".(int)$_GET['precio'].", 
            ".($_GET['disponible']? 1 : 0)."
        )";
    
    if(!@$bd->query($sql)){
        $res = $bd->query("SELECT COUNT(*) AS 'token' FROM productos WHERE cod = '".$_GET['cod']."'");
        $res->fetchArray(SQLITE3_NUM)[0]? exit('{"error":"Este cod ya existe"}'.header('HTTP/1.0 400'))
            : exit('{"error":"Error agregando registro. Error indeterminado."}'.header('HTTP/1.0 400'));
    }
}

if(isset($_GET['delete'])){
    $_GET['cod']?? exit('{"error":"Falta parametro cod."}'.header('HTTP/1.0 400'));
    
    $sql = "DELETE FROM productos WHERE cod = '" . addslashes($_GET['cod']) . "'";
    $bd->query($sql);
}

$productos = $bd->query('SELECT * FROM productos');
//var_dump((object)$productos->fetchArray(SQLITE3_ASSOC)); exit;
$retorno = array();
while($item = $productos->fetchArray(SQLITE3_ASSOC)){
    unset($item['pk']);
    foreach($item AS $prop => $valor){$item[$prop] = is_numeric($valor)? ($valor + 0) : $valor;}
    $item = (object)$item;
    $item->disponible = (bool)$item->disponible;
    $retorno[] = $item;
}
header('HTTP/1.0 200');
exit(json_encode($retorno));