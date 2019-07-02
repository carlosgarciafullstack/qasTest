<?php

class TransformationsLibrary {
    private $level;
    private $headers;

    function __construct($depthLevelInXML = 2, $headers = NULL) {
        $this->level = $depthLevelInXML;
        $this->headers = $headers;
    }

    public function flattenXMLtoCSV($path_xml, $csv_file_output) {
        
        if ($this->validateXML($path_xml)) {
            $elements = $this->getXMLElements($path_xml, $this->level);

            if ($this->headers === NULL) {
                $this->headers = $this->getXMLHeaders($elements);
            }
            $this->writeToCvs($elements, $this->headers, $csv_file_output);

        } else {
            die('XML NOT FOUND');
        }
    }

    private function validateXML($path_xml) {
        $isValid = false;
        if (file_exists($path_xml)) {
            $isValid = true;
        }
        return $isValid;
    }

    private function getXMLElements($path_xml, $level) {

        // Xml to Class
        $elements = simplexml_load_file($path_xml);

        // Get children for the selected $level
        for ($i = 0; $i < $level; ++$i) {
            $elements = $elements->children();
        }
        return $elements;
    }

    private function getXMLHeaders($elements) {
        // Search all headers in document
        $headers = [];

        foreach($elements as $key => $value) {
            $headersInElement = array_keys(get_object_vars($value));

            foreach($headersInElement as $header) {
                if (!in_array($header, $headers)) {
                    array_push($headers, $header);
                }
            }
        }
        return $headers;
    }

    private function writeToCvs($elements, $headers, $path_csv_output) {

        // Open CSV file
        $output_file = fopen($path_csv_output, 'w');

        // Put headers in csv to slipt ;
        fputcsv($output_file, array_values($headers), ';');

        // Put elements
        foreach($elements as $key => $value) {

            $element = get_object_vars($value);
            $csv_line = [];

            foreach($headers as $header) {
                if (isset( $element[$header]) ) {
                    array_push($csv_line, $element[$header]);
                } else {
                    // If it is not defined, we use empty string
                    array_push($csv_line, '');
                }
            }

            // White
            fputcsv($output_file, array_values($csv_line), ';');
        }

        // Close CSV file
        fclose($output_file);
    }
}

// If I know the headers
// Some example headers
$headers = ['name_header', 'colour_header', 'sku_header'];
$transformations1 = new TransformationsLibrary(2, $headers);
$transformations1->flattenXMLtoCSV('aplanamiento.xml','well_known_headers.csv');

// If I do not know the headers
$transformations2 = new TransformationsLibrary();
$transformations2->flattenXMLtoCSV('aplanamiento.xml','unknown_headers.csv');

?>