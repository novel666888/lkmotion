<?php

namespace common\util;

use PHPExcel;
use PHPExcel_IOFactory;

ini_set("memory_limit", "2048M");
set_time_limit(0);

class Excel
{
    private $headers = [
        'Content-Type' => 'application/vnd.ms-excel',
        'Content-Disposition' => 'filename=%s',
    ];

    public static function init($file_name)
    {
        $static = new static;
        $static->_setFileName($file_name);
        $static->php_excel = new PHPExcel();
        return $static;
    }

    public function setRowTitle($row_title)
    {
        $row_range = range('A', 'Z');
        $this->row_title = $row_title;
        $index = 0;
        foreach ($row_title as $_v) {
            $_row = $row_range[$index] . '1';
            $this->php_excel->setActiveSheetIndex()->setCellValue($_row, $_v);
            $index++;
        }
        return $this;
    }

    public function setRowData($data)
    {
        $row_range = range('A', 'Z');
        $index = '2';

        $data_key = array_keys($this->row_title);
        foreach ($data as $_k => $_v) {
            foreach ($data_key as $_index => $_key) {
                $_row = $row_range[$_index] . $index;
                $_value = isset($_v[$_key]) ? $_v[$_key] : '';
                $this->php_excel->getActiveSheet()->setCellValue($_row, $_value);
            }
            $index++;
        }

        return $this;
    }

    public function export()
    {
        ob_end_clean();
        ob_start();
        foreach ($this->headers as $_k => $_v) {
            header($_k . ':' . $_v);
        }
        $objWriter = PHPExcel_IOFactory::createWriter($this->php_excel, 'Excel5');

        $objWriter->save('php://output');
        ob_end_flush();
    }

    private function _setFileName($file_name)
    {
        $this->file_name = $file_name;
        $this->headers['Content-Disposition'] = vsprintf($this->headers['Content-Disposition'], [$file_name]);
    }

}