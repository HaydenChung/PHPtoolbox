<?php

class Table{

    private $_sqlResult;

    public function __construct($sqlResult){
        $this->_sqlResult = $sqlResult;
    }

    public function build(){
        $header = '';
        foreach($this->_sqlResult[0] as $indexName=>$value){
            $header .= $this->td($indexName);            
        }
        $header = $this->thead($this->tr($header));

        $temp = '';
        $body = '';
        foreach($this->_sqlResult as $row){
            $temp = '';
            foreach($row as $val){
                $temp .= $this->td($val);
            }
            $body .= $this->tr($temp);
        }
        $body = $this->tbody($body);
        return $this->tableCont($header.$body);
    }

    private function tableCont($value){
        return "<table>{$value}</table>";
    }

    private function tr($value){
        return "<tr>{$value}</tr>";
    }

    private function td($value){
        return "<td>{$value}</td>";
    }

    private function th($value){
        return "<th>{$value}</th>";
    }

    private function thead($value){
        return "<thead>{$value}</thead>";
    }

    private function tbody($value){
        return "<tbody>{$value}</tbody>";
    }

    private function tfoot($value){
        return "<tfoot>{$value}</tfoot>";
    }
}