<?php

namespace Utils;

Class Util
{
    public function __construct()
    {
    }

    private function makeNonNestedRecursive(array &$out, $key, array $in): void{
        foreach($in as $k=>$v){
            if(is_array($v)){
                $this->makeNonNestedRecursive($out, $key . $k . '_', $v);
            }else{
                $out[$key . $k] = $v;
            }
        }
    }

    private function makeNonNested(array $in): array{
        $out = array();
        $this->makeNonNestedRecursive($out, '', $in);
        return $out;
    }

    public function payloadToCsv($array, $header)
    {
        $fp = fopen($header . ".csv", "w");
        fputcsv($fp, array_keys($array["content"][0]));
        foreach ($array["content"] as $fields) {
            fputcsv($fp, $this->makeNonNested($fields));
        }
        if(!empty($array["links"])) {
            fputcsv($fp, array_keys($array["links"]));
            fputcsv($fp, $array["links"]);
        }
        fclose($fp);
    }
}