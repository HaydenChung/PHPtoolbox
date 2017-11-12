<?php
session_start();

include_once('./php/libraries/spout-2.7.3/src/Spout/Autoloader/autoload.php');
use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Common\Type;

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $before = microtime(true);

    $filePath = $_FILES['upload_file']['tmp_name'];

    $importManage = new ImportManage;
    $jsonPath = $importManage->storeExcelToJSON($filePath, './json_'.date('Y-m-d_his').'.json');
    $result = $importManage->activityFeed($jsonPath);


    echo "<pre>";

    var_dump($result);

    echo "</pre>";

    echo microtime(true)-$before;
}



Class ImportManage {

    public function __construct() {

    }
    
    const TABLEHEADER = ['class','no','eng_name','chi_name','gender','student_number','groupname','teachers','MO0','MO1','MO2','MO3','MO4','MO5','MO6','MO7','TU0','TU1','TU2','TU3','TU4','TU5','TU6','TU7','WE0','WE1','WE2','WE3','WE4','WE5','WE6','WE7','TH0','TH1','TH2','TH3','TH4','TH5','TH6','TH7','FR0','FR1','FR2','FR3','FR4','FR5','FR6','FR7'];

    public static function excelToArray($filePath) {
        
        $reader = ReaderFactory::create(Type::XLSX);
        $reader->open($filePath);
        $result = [];
        $maxIndex = count(self::TABLEHEADER)-1;

        $i = 0;
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $result[$i] = [];
                foreach($row as $cellIndex=>$cell){
                    if($cellIndex>$maxIndex) break;
                    if($cell == null) continue;
                    $result[$i][self::TABLEHEADER[$cellIndex]] = $cell;
                }
                $i++;
            }
        }

        return $result;
    }

    public function storeExcelToJSON($inputFilePath, $outputFilePath) {

        $data = self::excelToArray($inputFilePath);

        $fp = fopen($outputFilePath, 'w');
        fwrite($fp, json_encode($data));
        fclose($fp);

        return $outputFilePath;
    }

    public function activityFeed($filePath) {

        $data = json_decode(file_get_contents($filePath), true);

        $result = [];
        $currGroup = '';
        $matches = [];

        foreach($data as $row) {
            if(!is_int($row['no'])) continue;
            $currGroup = $row['groupname'];
            if(!isset($result[$currGroup])) {
                preg_match('/[^-]*-([^-]+)-.*/', $currGroup, $matches);
                $result[$currGroup] = ['name'=> $currGroup, 'subject'=> $matches[1], 'form'=> $row['class'][0]];
            }
            if($result[$currGroup]['form'] != $row['class'][0] && $result[$currGroup]['form'] != 0) $result[$currGroup]['form'] = 0;
        }

        return $result;
    }

    public function scheduleFeed($filePath) {

    }

    public function userJoinFeed($filePath) {

    }

}

class OrderProcess {

    private $_orderName = '',
    $_orders = [],
    $_allowedOrders = [];

    public function __construct($orderName, $orders, $allowedOrders) {

        $this->_orderName = $orderName;
        $this->_orders = $orders;
        $this->_allowedOrders = $allowedOrders;
        
        $this->checkOrders();
    }

    private function _checkOrders($orders = null) {

        $result = null;
        if($orders == null) $orders = $this->_orders;

        foreach($orders as $className=>$func) {

            if(is_array($func)) {
                if(!isset($this->_allowedOrders[$func[0]])) {
                    $result = $func[0];
                }
                
                if(!in_array($func[1], $this->_allowedOrders[$func[0]])) {
                    $result = $func[1];
                }

                if($result !== null) break;
                continue;
            }

            if(!in_array($func, $this->_allowedOrders)) {
                $result = $func;
                break;
            }

        }

        $result === null ? return true : throw new Exception("Unaccepted class or function!:{$result}");

        // return true;
    }

    public function process($order = null) {

        $result = null;
        $index = 0;

        if($order === null) {
            if(!isset($_SESSION['OrderProcess'][$this->_orderName])) {
                $_SESSION['OrderProcess'][$this->_orderName] = 0;
            }
            
            $index = $_SESSION['OrderProcess'][$this->_orderName];
            $order = $this->_orders[$index];
        }

        if($this->_checkOrders($order)) $result = call_user_func_array($order, $_SESSION['OrderProcess']['result'][$this->_orderName]);

        if(!$result) return false;

        $_SESSION['OrderProcess']['result'][$this->_orderName] = $result;
        $_SESSION['OrderProcess'][$this->_orderName]++;

    }
}

// function 

?>
<form action='' method='post' enctype="multipart/form-data">
    <input type='file' name='upload_file'>
    <input type='submit'>
</form>
