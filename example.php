<?php
namespace filesDataStorageExample;

use filesDataStorage\Collection as Collection;
use filesDataStorage\QueryFilter as QF;
use filesDataStorage\filesDataStorage as filesDS;

$folder = __DIR__ . '/data';
$collection = 'example';

include './filesDataStorage.php';
try {
    $filesDS = new filesDS ( $folder );
    $collection = $filesDS->selectCollection ( 'testme' );

    /* $x = new \stdClass();
      $y = new \stdClass();
      $y->name = 'Feofan';
      $y->surname = 'Zemskoj';

      $x->hello = 'world';
      $x->world = 'must die';
      $x->owner = $y;

      $d['datas'] = $x;
      $d['dig'] = rand ( 110240, 160454 );
      $collection->save ( $d );
     */


    $data = array ();
    $data['dig'] = QF::compare ( QF::CMP_GTE, 2 );

    $data['datas'] = new \stdClass();
    $data['datas']->world = 'must die';


    $queryFilter = new QF();
    $queryFilter->setOrderDirection ( QF::ORDER_ASC );
    $queryFilter->setOrderField ( 'hello' );
    $queryFilter->setData ( $data );

    $result = $collection->get ( $queryFilter );

    var_dump ( $result );
} catch (\Exception $e) {
    echo 'Some errors happened:<br />';
    foreach ($e as $exception) {
        echo $e->getMessage () . '<br />';
    }
}

