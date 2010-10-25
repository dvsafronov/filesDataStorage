<?php

/**
 * filesDataStorage
 *
 * NoSQL file-based database
 *
 * @package filesDataStorage
 * @author Denis xmcdbx Safonov (mcdb@mcdb.ru)
 * @copyright Copyright (c) 2010
 * @version 0.0.1
 * @license GPL v.3 http://www.gnu.org/licenses/gpl.txt
 *
 */
namespace filesDataStorage;

class DataStorage {
    private $folder;

    public function __construct ( $folder ) {
        if (\substr ( $folder, -1 ) == '/') {
            $folder = \substr ( $folder, 0, -1 );
        }
        $this->folder = $folder;
        return true;
    }

    public function save ( $collection, Array $data ) {
        if (false == (\key_exists ( '_id', $data ))) {
            return false;
        }
        $folder = $this->folder . '/' . $collection . '/';
        if (false === \file_exists ( $folder )) {
            \mkdir ( $folder );
            \chmod ( $folder, 0777 );
        }
        $filename = $folder . '/' . $data['_id'] . '.json';
        $data = \json_encode ( $data );
        return strlen ( $data ) == \file_put_contents ( $filename, $data );
    }

    public function remove ( $collection, $data ) {
        $records = $this->get ( $collection, $data );
        $folder = $this->folder . '/' . $collection;
        for ($i = 0, $ca = count ( $records ); $i < $ca; $i++) {
            if (isset ( $records[$i]['_id'] )) {
                \unlink ( $folder . '/' . $records[$i]['_id'] . '.json' );
            }
        }
        return true;
    }

    public function get ( $collection, $data, $limit = 0, $offset = 0,
            $order = 0, $orderBy = '_id' ) {
        $folder = $this->folder . '/' . $collection . '/';
        \chdir ( $folder );
        $filter = '*';
        if (\key_exists ( '_id', $data )) {
            $filter = (string) $data['_id'];
        }
        $files = \glob ( $filter . '.json' );
        if (\count ( $files ) == 0) {
            return false;
        }
        unset ( $data['_id'] );
        $caData = \count ( $data );
        $return = array ();
        for ($i = 0, $ca = \count ( $files ); $i < $ca; $i++) {
            if ($caData == 0) {
                \array_push ( $return, \json_decode ( \file_get_contents ( $folder . $files[$i] ), true ) );
            } else {
                $recordContent = \file_get_contents ( $folder . $files[$i] );
                $record = \json_decode ( $recordContent );
                $dataCoincidence = 0;
                foreach ($record as $key => $value) {
                    if (isset ( $data[$key] )) {
                        $dataCoincidence += $this->checkData ( $data, $key, $value );
                    }
                    if (\is_object ( $record->$key )) {
                        foreach ($value as $subkey => $subvalue) {
                            $dataCoincidence += $this->checkData ( $data[$key], $subkey, $subvalue );
                        }
                    }
                }
                if ($dataCoincidence == $caData) {
                    \array_push ( $return, json_decode ( $recordContent, true ) );
                }
            }
        }
        if ($order != 0) {
            if ($order != 2) {
                if ($orderBy == '_id') {
                    if ($order == 1) {
                        sort ( $return );
                    }
                    if ($order == -1) {
                        rsort ( $return );
                    }
                } else {
                    usort ( $return, function ($a, $b) use ($orderBy, $order) {
                                if (($pos = \strpos ( $orderBy, '.' ))) {
                                    $orderByParent = \substr ( $orderBy, 0, $pos );
                                    $orderByKinder = \substr ( $orderBy, ++$pos );
                                    unset ( $pos );
                                    $res = strcmp ( $a[$orderByParent][$orderByKinder], $b[$orderByParent][$orderByKinder] );
                                } else {
                                    $res = strcmp ( $a[$orderBy], $b[$orderBy] );
                                }
                                if ($order == -1) {
                                    return $res * -1;
                                } else {
                                    return $res;
                                }
                            } );
                }
            } else {
                shuffle ( $return );
            }
        }
        if ($limit > 0 || $offset > 0) {
            if ($limit == 0) {
                $limitt = null;
            }
            $return = \array_slice ( $return, $offset, $limit );
        }
        return $return;
    }

    private function checkData ( $data, $key, $value ) {
        $dataCoincidence = 0;
        if (\is_array ( $data[$key] )) {
            $op = key ( $data[$key] );
            if ($op == '$gte' && $value >= $data[$key][$op]) {
                $dataCoincidence++;
            }
            if ($op == '$lte' && $value <= $data[$key][$op]) {
                $dataCoincidence++;
            }
            if ($op == '$gt' && $value > $data[$key][$op]) {
                $dataCoincidence++;
            }
            if ($op == '$lt' && $value < $data[$key][$op]) {
                $dataCoincidence++;
            }
            if ($op == '$ne' && $value != $data[$key][$op]) {
                $dataCoincidence++;
            }
            if ($op == '$btw'
                    && \is_array ( $data[$key][$op] )
                    && count ( $data[$key][$op] ) == 2
                    && $value > (int) $data[$key][$op][0]
                    && $value < (int) $data[$key][$op][1]) {
                $dataCoincidence++;
            }
            if (($op == '$in' || $op == '$nin' ) && \is_array ( $data[$key][$op] )) {
                if (\is_array ( $value )) {
                    if (($op == '$in') == (count ( array_intersect ( $value, $data[$key][$op] ) ) > 0)) {
                        $dataCoincidence++;
                    }
                } else {
                    if (($op == '$in') == \in_array ( $value, $data[$key][$op] )) {
                        $dataCoincidence++;
                    }
                }
            }
            if ($op == '$all' && \is_array ( $data[$key][$op] ) && \is_array ( $value )) {
                if (count ( array_intersect ( $value, $data[$key][$op] ) ) == count ( $data[$key][$op] )) {
                    $dataCoincidence++;
                }
            }
        } else if ((\is_array ( $value ) && \in_array ( $data[$key], $value )) || $data[$key] == $value) {
            $dataCoincidence++;
        }
        return $dataCoincidence;
    }

}