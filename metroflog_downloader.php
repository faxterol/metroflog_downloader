<?php
/**
 * @author Luis Carlos
 * @copyright 2010
 */
ob_start();
session_start();

$usuario = empty($_GET['u'])? '':$_GET['u'];
if(empty($usuario)) exit;

//extraemos los principales datos
$cu = curl_init();
curl_setopt($cu, CURLOPT_URL,"http://www.metroflog.com/".$usuario); 
curl_setopt($cu, CURLOPT_USERAGENT, "	Mozilla/5.0 (Windows; U; Windows NT 5.1; es-ES; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3 ( .NET CLR 3.5.30729) " );
curl_setopt($cu, CURLOPT_RETURNTRANSFER, 1);
$metroflog = curl_exec($cu);
curl_close($cu);

//extraemos el numero identificador del usuario
preg_match("/(uc\=([0-9]*?));/is",$metroflog,$sids);
//$iduser = eregi_replace("uc=([0-9]*);",'\\1',$metroflog); 
$iduser = $sids[2];

//ahora extraemos los meses de actividad.
preg_match("/(meses\=new Array\((.*?)\))/is",$metroflog,$smse);
$meses = explode(",",$smse[2]);


$urlsfotos = array();

foreach($meses as $mesan){
    $mesan = trim($mesan);
    //ya que tenemos los meses de actividad... ahora buscamos todas las fotografias con sus respectivas direcciones para las fotos originales.
    $mfo = curl_init("http://ww2.metroflog.com/visor_js.php?uc=".$iduser."&ym=".$mesan."&date_format=%d/%m/%Y&pos=A");
    curl_setopt($mfo, CURLOPT_USERAGENT, "	Mozilla/5.0 (Windows; U; Windows NT 5.1; es-ES; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3 ( .NET CLR 3.5.30729) " );
    curl_setopt($mfo, CURLOPT_RETURNTRANSFER, 1);
        
    $estemescontent = curl_exec($mfo);
    
    curl_close($mfo);
    
    //ya que tenemos el contenido captamos solo las variables.
    preg_match("/(var pos=0;(.*?)fotos =)/is",$estemescontent,$varsfotos);
    
    $fotosvars = explode("\n",trim($varsfotos[2]));
    
    foreach($fotosvars as $foto){ //(.*?)
        preg_match("/(fotos_(.*?)\[(.*?)\] \= new Array\((.*?),'(.*?)', '(.*?)', '(.*?)', '(.*?)', '(.*?)'\);)/is",$foto,$fotoarrayjs);

        if(empty($fotoarrayjs[9])){
            $urlfoto = "http://www.metroflog.com/".$usuario."/".$fotoarrayjs[7];
        }
        else{
            $urlfoto = "http://www.metroflog.com/".$usuario."/".$fotoarrayjs[7]."/".$fotoarrayjs[9];
        }

                
        if(!in_array($urlfoto,$urlsfotos)){
            $urlsfotos[]=$urlfoto;
        }
    }
}

$fotos = array();

echo 'Se descargaran '.count($urlsfotos).' fotos <br />';

foreach($urlsfotos as $uf){
    $ff = curl_init($uf);
    curl_setopt($ff, CURLOPT_USERAGENT, "	Mozilla/5.0 (Windows; U; Windows NT 5.1; es-ES; rv:1.9.2.3) Gecko/20100401 Firefox/3.6.3 ( .NET CLR 3.5.30729) " );
    curl_setopt($ff, CURLOPT_RETURNTRANSFER, 1);
    $pf = curl_exec($ff);
    curl_close($ff);
    
    preg_match("/(<img alt=\"(.*?)\" src=\"(.*?)\" id=\"foto_actual\" name=\"foto_actual\" class=\"im\")/is",$pf,$fotodato);
    $fotos[] = $fotodato[3];
}

print_r($fotos);

if(count($fotos) > 0){
    @mkdir($usuario);
    $contador = 1;
    foreach($fotos as $foto){
        $df = explode("/",$foto);
        if(!file_exists($usuario.'/'.$df[7])){
            $im = @imagecreatefromjpeg($foto);
            if(!$im){
                echo $contador.') <strong>'.$df[7].'</strong> tuvo problemas para descargarse.<br />';
            }
            else{
                imagejpeg($im,$usuario.'/'.$df[7]);
                imagedestroy($im);
                echo $contador.') <strong>'.$df[7].'</strong> ha sido descargado con exito.<br />';
            }
        }
        else{
            echo $contador.') <strong>'.$df[7].'</strong> ya habia sido descargado.<br />';
        }
        $contador++;
    }
}
?>