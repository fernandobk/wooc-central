<?php
/* Con este script llamamos a una api "central" que nos devolverá
los codigos, disponibilidades y precios de cada producto */

$url = ($_SERVER['HTTP_HOST'] === 'wooc.localhost')? 'https://wooc-central.localhost/api.php'
    : 'https://hfbk.000webhostapp.com/wooc-central/api.php';

$token = '123';
$data = file_get_contents($url.'?token='.$token, false, 
    stream_context_create(array("ssl"=>array("verify_peer"=>false,"verify_peer_name"=>false)))
);

if(explode(' ', $http_response_header[0])[1] === '200'){
    // Cargamos el objeto con los datos
    //exit($data);
    $data = json_decode($data);

    ($_SERVER['HTTP_HOST']==='wooc.localhost')? ($bd = new mysqli(null, 'root', '', 'wooc'))
        : null;

    $bd->connect_errno? exit('Error conectando a la base de datos') : null;

    foreach($data AS $i => $item){
        $post_id = "SELECT post_id FROM wooc_postmeta WHERE meta_value = '$item->cod'";
        //exit($post_id);
        $post_id = $bd->query($post_id);
        $post_id = $post_id? $post_id->fetch_object() : exit('Error consultando a la base de datos. '.$bd->error);
        
        if(isset($post_id)){
            $post_id = $post_id->post_id;
            //exit('<h1>'.var_export($post_id, true) . '</h1>');
            
            $sql = "UPDATE wooc_postmeta 
            SET meta_value = '$item->precio'
            WHERE (meta_key = '_price' AND post_id = $post_id)
                OR (meta_key = '_regular_price' AND post_id = $post_id)";
            
            $bd->query($sql)?: exit('Error escribiendo datos en la base de datos. '.$bd->error);

            $sql = "UPDATE wooc_postmeta
            SET meta_value = '".($item->disponible? 'instock':'outofstock')."'
            WHERE (meta_key = '_stock_status' AND post_id = $post_id)";

            $bd->query($sql)?: exit('Error escribiendo datos en la base de datos. '.$bd->error);
        }
        
    }
}
