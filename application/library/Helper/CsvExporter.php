<?php

class Helper_CsvExporter
{

    private $exportCharset = 'gbk';
    protected $charset = 'utf-8';
    protected $dataField = null;
    protected $listFields = null;
    protected $rows = null;

    public function __construct(&$rows, $listFieldConfig, $dataField = null)
    {
        $this->rows = $rows;
        $this->listFields = $listFieldConfig;
        $this->dataField = $dataField;
    }

    public function setCharset($charset)
    {
        $this->charset = strtolower($charset);
    }

    public function export($file = null)
    {
        if ($file === null) {
            $file = 'data';
        }
        $fileName = $file . "-" . date("Y-m-d_H-i-s") . '.csv';
        header("Cache-Control: public");
        header("Content-Type: application/force-download");
        header("Content-Disposition: attachment; filename=$fileName");

        echo $this->_exportLine($this->listFields) . "\r\n";
        foreach ($this->rows as $origRow) {
            $row = $this->_getRow($origRow);
            echo $this->_exportLine($row) . "\r\n";
        }
    }

    private function _getRow($row)
    {
        $retRow = array();
        if ($this->dataField) {
            $retRow = $row[$this->dataField];
        } else {
            $retRow = $row;
        }

        return $retRow;
    }

    private function _exportLine(&$row)
    {
        $line = '';
        foreach ($this->listFields as $field => $name) {
            $v = str_replace('"', '\"', strip_tags($row[$field]));
            if (is_numeric($v) && strlen($v) > 10) {
                $v = '*' . $v;
            }
            $line .= '"' . $v . '",';
        }

        $line = substr($line, 0, -1);
        if ($this->charset !== $this->exportCharset) {
            $line = mb_convert_encoding($line, $this->exportCharset, $this->charset);
        }
        return $line;
    }

}

?>
