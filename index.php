<?php

/**
 * Функция генерирует уникальный баркод для заказа
 * @event_id - int(11) - уникальный ид события. У каждого события есть свое название, описание, расписание, цены и свой уникальный event_id соответственно
 * @event_date - varchar(10) - дата и время на которое были куплены билеты
 * @ticket_adult_price - int(11) - цена взрослого билета на момент покупки
 * @ticket_adult_quantity - int(11) - количество купленных взрослых билетов в этом заказе
 * @ticket_kid_price - int(11) - цена детского билета на момент покупки
 * @ticket_kid_quantity - int(11) - количество купленных детских билетов в этом заказе
 * @return - числовой баркод
 */
function generateBarcode ($event_id, $event_date, $ticket_adult_price, $ticket_adult_quantity, $ticket_kid_price, $ticket_kid_quantity) {
  $statusBarCode = false;
  do {
    $bar = rand();
    $url = 'https://api.site.com/book';
    $options = array(
      'event_id' => $event_id, 
      'event_date' => $event_date, 
      'ticket_adult_price' => $ticket_adult_price, 
      'ticket_adult_quantity' => $ticket_adult_quantity, 
      'ticket_kid_price' => $ticket_kid_price, 
      'ticket_kid_quantity' => $ticket_kid_quantity, 
      'barcode' => $bar,
    );
  
    $ch = curl_init();
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt ($ch, CURLOPT_URL, $url.'?'.http_build_query($options));
  
    $response = curl_exec($ch);
    $data = json_decode($response, true);
    curl_close($ch);
  
    if ($data['message'] === 'order successfully booked') {
      $statusBarCode = true;
    }
  } while ($statusBarCode);
  return $bar;
}

/**
 * Функция подтверждения корректного баркода
 * @barcode
 * @return @data
 */
function verificationBarcode ($barcodeReserve) {
  $url = 'https://api.site.com/approve';
  $options = array(
      'barcode' => $bar,
  );

  $ch = curl_init();
  curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt ($ch, CURLOPT_URL, $url.'?'.http_build_query($options));

  $response = curl_exec($ch);
  $data = json_decode($response, true);
  curl_close($ch);
  
  if (!$data['error']) {
    return true;
  } else {
    return false;
  }
}

/**
 * Добавляет в БД заказ
 * @event_id - int(11) - уникальный ид события. У каждого события есть свое название, описание, расписание, цены и свой уникальный event_id соответственно
 * @event_date - varchar(10) - дата и время на которое были куплены билеты
 * @ticket_adult_price - int(11) - цена взрослого билета на момент покупки
 * @ticket_adult_quantity - int(11) - количество купленных взрослых билетов в этом заказе
 * @ticket_kid_price - int(11) - цена детского билета на момент покупки
 * @ticket_kid_quantity - int(11) - количество купленных детских билетов в этом заказе
 * @return - true - если успешно записано, false - если запрос не записан в БД
 */
function addOrders ($event_id, $event_date, $ticket_adult_price, $ticket_adult_quantity, $ticket_kid_price, $ticket_kid_quantity) {
  $equal_price = $ticket_adult_price * $ticket_adult_quantity + $ticket_kid_price * $ticket_kid_quantity;
  $created = date("Y-m-d H:i:s");

  $barcode  = generateBarcode($event_id, $event_date, $ticket_adult_price, $ticket_adult_quantity, $ticket_kid_price, $ticket_kid_quantity);
  if (verificationBarcode ($barcode)) {
    $result = mysqli_query(
      connect(), 
      "INSERT into orders (event_id, event_date, ticket_adult_price, ticket_adult_quantity, ticket_kid_price, ticket_kid_quantity, barcode, equal_price, created) 
        values ('$event_id', '$event_date','$ticket_adult_price', '$ticket_adult_quantity', '$ticket_kid_price', '$ticket_kid_quantity', '$barcode', '$equal_price', '$created')"
    );
    var_export($result);
  }
  
}

//создаем подключение к серверу БД
static $connection = 'null';
function connect () { 
  if($connection === null) {
    $connection = mysqli_connect('localhost','root','', 'nevatrip') or die('Connect Error');    
  }
  return $connection;
}

//Переменные используемые для заказа 
$event_id = 4; //уникальный ид события. У каждого события есть свое название, описание, расписание, цены и свой уникальный event_id соответственно
$event_date = date("Y-m-d", time()+60*60*24*2); //дата и время на которое были куплены билеты
$ticket_adult_price = 800; //цена взрослого билета на момент покупки
$ticket_adult_quantity = 1; //количество купленных взрослых билетов в этом заказе
$ticket_kid_price = 600; //цена детского билета на момент покупки
$ticket_kid_quantity = 2; //количество купленных детских билетов в этом заказе

//вызов функции для добавления заказа в БД
addOrders ($event_id, $event_date, $ticket_adult_price, $ticket_adult_quantity, $ticket_kid_price, $ticket_kid_quantity);

//закрываем подключение к БД
mysqli_close(connect());