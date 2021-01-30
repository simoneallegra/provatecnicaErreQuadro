<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

//parto da http://localhost:8000/....

//funzione supplementare per l'inserimento di un elemento in gif_providers
Route::get('/insertProvider', function () {
    //esempio di url /insertProvider?id=1&key=1&identification=tenor_gif112sd&info=%20bgibeig
    //-------------- /insertProvider?id=2&key=2&identification=giphy_gif&info=%20aaaaaaaa

    $con = mysqli_connect('localhost', 'root', '', 'provatecnicadb');
    $id_get  = $_GET['id'];
    $key_get  = $_GET['key'];
    $identification_get  = $_GET['identification'];
    $info_get  = $_GET['info'];

    $credits = array("id"=>$id_get,"key"=>$key_get);
    $result = json_encode($credits);

    //query
    $sql = "INSERT INTO gif_providers(IDprovider, info, counter_calls, credits) VALUES ('$identification_get', '$info_get', '0', '$result')";

    $res = mysqli_query($con,$sql);

    if(!$res) echo "error: $res";


    mysqli_close($con);
});

Route::get('/providers', function () {

    echo json_encode(function_selected());
    //connessione
    
});

Route::get('/provider/{identifier}/stats', function ($identifier) {


    $con = mysqli_connect('localhost', 'root', '', 'provatecnicadb');

    //query per trovare l'elemento nel gif_providers
    $sql = "SELECT * FROM gif_providers WHERE IDprovider='$identifier'";

    $res = mysqli_query($con,$sql);
    
    $ctrl = false; //controllo che la query che ho fatto torna o meno un risulatao
    
    if($res){

       
        while($row = mysqli_fetch_array($res)){
            $calls = $row[3]; //salvo  il numero di chiamate
            $id_provider = $row[0]; // salvo il provider selezionato
            
            //query per trovare l'elemento di relazione all'id trovato nel gif_providers
            $sql2 = "SELECT * FROM relationsprovkey WHERE IDproviderR=$id_provider";
            $res2 = mysqli_query($con,$sql2);
            
            //se while gira allora $ctrl si setta true, se invece $row è nullo $ctrl rimane false
            $ctrl = true;
            
        }
           
        if($ctrl){

            $result = array();

            while($row = mysqli_fetch_array($res2)){
                $relation_calls= $row[3]; //salvo l'id della keyword associata
                $id_key = $row[2]; //salvo l'id della key che torna sql2

                //query per trovare gli elementi nei keyword legato alla relazione trovata nella sql2
                $sql3= "SELECT * FROM keywords WHERE id=$id_key";

                $res3 = mysqli_query($con,$sql3);            
            }

            if($res3){
                //stampa dell'array da aggiungere a quello finale
                while($row = mysqli_fetch_array($res3)){
                    array_push($result,array('keyword'=>$row[1], 'calls'=>$relation_calls));
                }
                //response con un array di due elementi: le calls e l'array di elementi selezionati in $res3
                echo json_encode(array("calls"=>$calls, "Keywords"=>$result));

            }else echo 'error';
        }else echo 'error 404 - Provider does not exist';
    }else echo 'error';
    
    mysqli_close($con); 
});

Route::get('/gifs/{keyword}', function ($keyword) {

    //Esempi di ricerche da effettuare con relative chiavi

    //"https://api.tenor.com/v1/search?q=$keyword&key=LIVDSRZULELA&limit=5"
    //"https://api.giphy.com/v1/gifs/search?q=pippo+baudo&api_key=nJH2iYpDsx3sYRgOXOVRi0BHYmuIe4Sk&limit=5"

    // INIZIO RICERCA
    // Prova: echo "0) $keyword \n";
    //se non ci sono caratteri alfanumerici o non ci sono underscore sostituisci '_'

        for($i = 0; $i < strlen($keyword); $i++){
            if (!ctype_alnum($keyword[$i]) && !strpos($keyword[$i], '_')){ 
                $keyword[$i]='_';
            } 
        }

    $keyword=strtolower($keyword);

    //applico il trim

    $keyword=trim($keyword, "_");

    $keyword=trim($keyword, " ");

    //------------------------------------------------interrogazione dell'API-------------------------------

    //seleziono tutti i provider
    $array_provider = function_selected();
    
    //nel seguente for tento di stampare tutti i rsultati ottenuti per tutti i provider

    for($j=0; $j < count($array_provider['providers']); $j++){

        //decodifico il campo json
        $key=json_decode(($array_provider['providers'][$j]['credits']),true);

        //Se lavora tenor
        if($array_provider['providers'][$j]['identifier']=='tenor_gif'){
            $string_to_search = $array_provider['providers'][$j]['description']."$keyword&key=".$key['key']."&limit=5";
        }
        //Se lavora giphy
        else if($array_provider['providers'][$j]['identifier']=='giphy_gif'){
            $keyword=str_replace("_", "+", $keyword); //piazzo + perchè lo richiede giphy
            
            $string_to_search = $array_provider['providers'][$j]['description']."$keyword&api_key=".$key['key']."&limit=5";
        }
        

        //client url indipendente dal tipo di provider interrogato
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $string_to_search);
        // echo $string_to_search;
        
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION,1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);

        $response = curl_exec($curl);
        $decode = json_decode($response, true);

        $result = array();
        

        //di seguito si ha la gestione della response ottenuto prima da tenor poi da giphy
        if($array_provider['providers'][$j]['identifier']=='tenor_gif'){
            for($i=0; $i <count($decode['results']); $i++){
                array_push($result, $decode['results'][$i]['url']);
            }
        }else if($array_provider['providers'][$j]['identifier']=='giphy_gif'){
            for($i=0; $i <count($decode['data']); $i++){
                array_push($result, $decode['data'][$i]['url']);
            }
        }


            //stampo risultati
            echo json_encode(array("results"=>$result));

            curl_close($curl);
        }

    // FINE RICERCA

    //-------------------------------------------Carico informazioni in DB----------------------------------------------

    $keyword=str_replace("_", " ", $keyword);
    $keyword=str_replace("+", " ", $keyword);

    $con = mysqli_connect('localhost', 'root', '', 'provatecnicadb');
    
    //controllo se l'elemento è gia presente, e non lo è lo inserisco
    $sql_sel_ctrl_keyword = "SELECT * FROM keywords WHERE keyword='$keyword'";
    $res_sel_ctrl_keyword = mysqli_query($con,$sql_sel_ctrl_keyword);

    if(mysqli_num_rows($res_sel_ctrl_keyword)==0){

        $sql_ins1 = "INSERT INTO keywords(keyword) VALUES ('$keyword')";

        $res1 = mysqli_query($con,$sql_ins1);
    }


    // carico la tabella di relazione------------

    $sql_sel = "SELECT id FROM keywords WHERE keyword='$keyword'";

    $res_sel = mysqli_query($con,$sql_sel);
    
    $result_sel = array();

    while($row = mysqli_fetch_array($res_sel)){
        array_push($result_sel, array('id'=>$row[0]));
    }

    
    for($k=0; $k<count($result_sel); $k++){
        $temp= $result_sel[$k]['id'];
        
        //controllo se l'elemento è già presente in relazione
        $sql_sel_ctrl = "SELECT IDproviderR, idkeywordR, counter FROM relationsprovkey WHERE idkeywordR='$temp'";

        $res_sel_ctrl = mysqli_query($con,$sql_sel_ctrl);

        //se è la prima volta
        if(mysqli_num_rows($res_sel_ctrl)==0){

            //carico da Tenor
            $sql_ins2 = "INSERT INTO relationsprovkey(IDproviderR, idkeywordR, counter) VALUES ('2','$temp', '0')";
            
            $res_ins2 = mysqli_query($con,$sql_ins2);

            //carico da Giphy
            $sql_ins3 = "INSERT INTO relationsprovkey(IDproviderR, idkeywordR, counter) VALUES ('3','$temp', '0')";
            
            $res_ins3 = mysqli_query($con,$sql_ins3);
        }else{
            //aggiorno il counter
            $result_sel_ctrl = array();

            while($row = mysqli_fetch_array($res_sel_ctrl)){
                array_push($result_sel_ctrl, array('counter'=>$row[2]));
            }

            $value_count = $result_sel_ctrl[0]['counter'];
            $new_value = $value_count + 1;
            $sql_upt = "UPDATE relationsprovkey SET counter = $new_value WHERE idkeywordR='$temp'";
            $res_upt = mysqli_query($con,$sql_upt);
        }

    }
    
    mysqli_close($con);

});

Route::get('/gifs/{keyword}/stats', function ($keyword) {


    //replay per interfarciarmi alle tabelle
    $keyword=str_replace("_", " ", $keyword);
    $keyword=str_replace("+", " ", $keyword);

    $value = 0;
    $e=false;

    $con = mysqli_connect('localhost', 'root', '', 'provatecnicadb');

    //query per ritrovare la parola chiave
    $sql = "SELECT id FROM keywords WHERE keyword='$keyword'";

    $res = mysqli_query($con,$sql);

    //query per ritrovare tutti i provider    
    $sql_provider = "SELECT id FROM gif_providers";

    $res_provider = mysqli_query($con,$sql_provider);

    $result_provider = array();

    while($row_provider = mysqli_fetch_array($res_provider)){
        array_push($result_provider, array('id'=>$row_provider[0]));
    }

    $result = array();

    for($i=2; $i-2<count($result_provider);$i++){

        while($row = mysqli_fetch_array($res)){
            //selezione per ogni provider la parola chiave e torno il counter
            $temp= $row[0];
            $sql_t = "SELECT counter FROM relationsprovkey WHERE idkeywordR='$temp' && IDproviderR =$i";
            $res_t = mysqli_query($con,$sql_t);
        
                while($row_t = mysqli_fetch_array($res_t)){
                    //sommo i counter per ogni provider
                    $value += $row_t['counter'];
                }
            
        }

        //se non trovo nulla..
        if($value == 0){
            $e = true;
            break;
        }else{
            $result["provider-".$i]=$value;
        }
        // echo "$value"."\n";
    
    }

    if(!$e)print_r($result);
    else print_r("Error 404");

    mysqli_close($con); 
    
    
});

Route::post('/provider/{identifier}', function ($identifier) {
    
});

function function_selected(){

    $con = mysqli_connect('localhost', 'root', '', 'provatecnicadb');


    //query
    $sql = "SELECT * FROM gif_providers";

    $res = mysqli_query($con,$sql);

    if($res){

    //stampo array con il risultato
    $result = array();

    while($row = mysqli_fetch_array($res)){
        $count=$row[3]; //prende l'attuale valore di counter_calls
        array_push($result,array('identifier'=>$row[1], 'description'=>$row[2], 'calls'=>($row[3]+1), 'credits'=>$row[4]));
    }
        $new_value = $count + 1;

        $sql2 = "UPDATE gif_providers SET counter_calls = $new_value";
        $res2 = mysqli_query($con,$sql2);
        if(!$res2) echo "error";
        

        return array("providers"=>$result);
    }   
}

?>