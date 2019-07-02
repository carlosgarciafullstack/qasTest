<?php

class CsvMergeLibrary {
    
    public static function MergeTwoCsvToANewOne($path_csv_one, $path_csv_two, $path_csv_output) {

        $headers_one = self::getHeaderFileCsv($path_csv_one);
        $headers_two = self::getHeaderFileCsv($path_csv_two);

        $headers = self::mergeHeaders($headers_one, $headers_two);
        self::writeHeadersToCvs($path_csv_output, $headers);

        self::writeToCvs($path_csv_one, $path_csv_output, $headers);
        self::writeToCvs($path_csv_two, $path_csv_output, $headers);
    }

    private static function getHeaderFileCsv($path_csv, $separator = ",") {
        $file = fopen($path_csv, "r");
        $headers = fgetcsv($file, $separator);
        fclose($file);
        return $headers;
    }

    private static function mergeHeaders($headers_one, $headers_two) {
        foreach($headers_two as $header) {
            if (!in_array($header, $headers_one)) {
                array_push($headers_one, $header);
            }
        }
        return $headers_one;
    }

    private static function writeHeadersToCvs($path_csv_output, $headers, $separator = ","){
        $output_file = fopen($path_csv_output, "w");
        fputcsv($output_file, array_values($headers), $separator);
        fclose($output_file);
    }

    private function getLineToWrite($headers, $headers_in_file, $data) {
        $csv_line = [];
        foreach($headers as $header) {
            $index = array_search( $header, $headers_in_file);
            if ( $index !== false) {
                array_push($csv_line, $data[$index]);
            } else {
                array_push($csv_line, '');
            }
        }
        return $csv_line;
    }

    private function writeToCvs($path_csv_input, $path_csv_output, $headers, $separator = ",") {

        $output_file = fopen($path_csv_output, "a");
        $input_file = fopen($path_csv_input, "r");

        $headers_in_file = fgetcsv($input_file, $separator);

        while (($data = fgetcsv($input_file, $separator)) == true) {
            $csv_line = self::getLineToWrite($headers, $headers_in_file, $data);
            fputcsv($output_file, array_values($csv_line), $separator);
        }

        fclose($input_file);
        fclose($output_file);
    }

    
}

CsvMergeLibrary::MergeTwoCsvToANewOne("file1.csv", "file2.csv", "output_file.csv");

?>