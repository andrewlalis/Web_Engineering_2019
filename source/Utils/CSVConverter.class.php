<?php

namespace Utils;

/**
 * Helps with converting a normal PHP array into a CSV representation, which is required for the API. Note that for some
 * arrays which have their own sub-arrays, such structures must be flattened to a one-dimensional array, so with more
 * complicated payloads, parsing JSON will be a lot less convoluted than trying to parse a flattened CSV representation.
 */
Class CSVConverter
{
    /**
     * Un-nests (or flattens) an array.
     * @param array $out A reference to an array which will fill up with the flattened data.
     * @param string $key The key which identifies the `in` array in its parent.
     * @param array $in The array to be un-nested.
     */
    private static function unNestRecursive(array &$out, string $key, array $in)
    {
        foreach ($in as $k=>$v) {
            if (is_array($v)) {
                static::unNestRecursive($out, $key . $k . '_', $v);
            } else {
                $out[$key . $k] = $v;
            }
        }
    }

    /**
     * A wrapper for the recursive call to un-nest an array.
     * @param array $in The array to un-nest.
     * @return array A one-dimensional array of all the data from the input array.
     */
    private static function unNest(array $in): array
    {
        $out = [];
        static::unNestRecursive($out, '', $in);
        return $out;
    }

    /**
     * Converts a PHP array to a CSV file.
     * @param array $array The array to convert.
     * @param string $header The name of the file which should hold the generated CSV.
     */
    public static function arrayToCsv(array $array, string $header)
    {
        $fp = fopen($header . ".csv", "w");
        fputcsv($fp, array_keys(static::unNest($array["content"][0])));
        foreach ($array["content"] as $fields) {
            fputcsv($fp, static::unNest($fields));
        }
        if(!empty($array["links"])) {
            fputcsv($fp, array_keys($array["links"]));
            fputcsv($fp, $array["links"]);
        }
        fclose($fp);
    }
}