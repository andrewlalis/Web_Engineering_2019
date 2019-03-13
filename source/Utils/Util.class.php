<?php

namespace Utils;

Class Util
{
    public function __construct()
    {
    }

    private function unNestRecursive(array &$out, $key, array $in): void{
        foreach($in as $k=>$v){
            if(is_array($v)){
                $this->unNestRecursive($out, $key . $k . '_', $v);
            }else{
                $out[$key . $k] = $v;
            }
        }
    }

    private function unNest(array $in): array{
        $out = array();
        $this->unNestRecursive($out, '', $in);
        return $out;
    }

    public function payloadToCsv($array, $header)
    {
        $fp = fopen($header . ".csv", "w");
        fputcsv($fp, array_keys($this->unNest($array["content"][0])));
        foreach ($array["content"] as $fields) {
            fputcsv($fp, $this->unNest($fields));
        }
        if(!empty($array["links"])) {
            fputcsv($fp, array_keys($array["links"]));
            fputcsv($fp, $array["links"]);
        }
        fclose($fp);
    }
}