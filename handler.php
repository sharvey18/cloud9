<?php

    require('socket.php');
    
    $key ='AIzaSyBtw_qMkQGcRrhK8gklOAJ_iy_QucgZoTA';
    
    if (!empty($_POST['action'])) {
        switch($_POST['action']) {
            case 'makes':
                findCarMakes();
                break;
            case 'models':
                findCarModels($_POST['make']);
                break;
            case 'engines':
                findCarEngines($_POST['make'], $_POST['model']);
                break;
            case 'save':
                save($_POST);
                break;
            case 'geocode':
                getGeoCode($_POST['zipcode']);
                break;
            default:
                echo json_encode($_POST);
                break;
        }
    }
    
    function findCarMakes()
    {
        global $connection;
        
        $query = "SELECT * FROM makes";
        $result = mysqli_query($connection, $query);
        
        $makes = mysqli_fetch_all($result);
        
        echo json_encode($makes);
    }
    
    function findCarModels($makeId)
    {
        global $connection;
        $query = "SELECT * FROM models WHERE make=".$makeId;
        $result = mysqli_query($connection, $query);
        
        $models = mysqli_fetch_all($result);
        
        echo json_encode($models);
    }
    
    function findCarEngines($makeId, $modelId)
    {
        global $connection;
        
        $query = "SELECT * FROM engines WHERE make=".$makeId." AND model=".$modelId;
        $result = mysqli_query($connection, $query);
        
        $engines = mysqli_fetch_all($result);
        
        echo json_encode($engines);
    }
    
    function save($post)
    {
        global $connection;
        
        $query = "SELECT makes.make AS Make,
                models.model AS Model,
                engines.size AS Engine
            FROM makes
            INNER JOIN models
                ON makes.id = models.make
            INNER JOIN engines
                ON models.id = engines.model
            WHERE makes.id=".$post['make']." AND models.id=".$post['model']." AND engines.id=".$post['engine'];
            
        $result = mysqli_query($connection, $query);
        
        $selection = mysqli_fetch_assoc($result);

        $query = "INSERT INTO salesleads (id, name, phone, email, zipcode, best, makes, models, engines, date)
                    VALUES(
                        '',
                        '".$post['name']."',
                        '".$post['phone']."',
                        '".$post['email']."',
                        '".$post['zipcode']."',
                        '".$post['best']."',
                        '".$selection['Make']."',
                        '".$selection['Model']."',
                        '".$selection['Engine']."',
                        NOW()
                        )";
        
        
        
        $result = mysqli_query($connection, $query);
        
        $send = array(
            'name' => $post['name'],
            'phone' => $post['phone'],    
            'email' => $post['email'],
            'zipcode' => $post['zipcode'],
            'best' => $post['best'],
            'make' => $post['Make'],
            'model' => $post['Model'],
            'engine' => $post['Engine']
        );
            
        echo ($result) ? json_encode($send) : 'false';
        
    }
    
    function getGeoCode($zip)
    {
        global $key;
        
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address=$zip&key=$key";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);
        
        if ($response['status'] == 'OK') {
            $geometry = $response['results'][0]['geometry']['location'];
        
            $list = getDealerList($geometry['lat'], $geometry['lng']);
            
            echo json_encode($list);
        } else {
            echo 'false';
        }
    }
    
    function getDealerList($lat, $lng)
    {
        global $key;
        
        $url = "https://maps.googleapis.com/maps/api/place/radarsearch/json?location=$lat,$lng&radius=5000&types=car_dealer&key=$key";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);
        
        if ($response['status'] == 'OK') {
            
            $dealers = array_slice($response['results'], 0, 10, true);
            
            $dealerList = array();
            foreach ($dealers as $key => $dealer) {
                $dealerList[$key]['lat']     = $dealer['geometry']['location']['lat'];
                $dealerList[$key]['lng']     = $dealer['geometry']['location']['lng'];
                $dealerList[$key]['placeId'] = $dealer['place_id'];
                $dealerList[$key]['details'] = getDealerDetails($dealer['place_id']);
            }
            return $dealerList;
        }
        
    }
    
    function getDealerDetails($id)
    {
        // global $key;
        $key = 'AIzaSyDUOPJrPAU0B_oU3mekcSNfGSyB4c1w_1o';
        
        $url = "https://maps.googleapis.com/maps/api/place/details/json?placeid=$id&key=$key";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = json_decode(curl_exec($ch), true);
        curl_close($ch);
        
        $result = array();
        
        if ($response['status'] == 'OK') {
            $result['name'] = $response['result']['name'];
            $result['address'] = $response['result']['formatted_address'];
            $result['phone'] = $response['result']['formatted_phone_number'];
            $result['rating'] = $response['result']['rating'];
            $result['url'] = $response['result']['website'];
        
            return $result;
        }
    }
?>