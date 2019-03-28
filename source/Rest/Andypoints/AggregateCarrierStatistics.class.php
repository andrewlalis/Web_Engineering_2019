<?php

namespace Rest\Andypoints;

use Rest\AndypointTypes\GetRequest;
use Rest\Endpoint;
use Rest\Response;

/**
 * This endpoint returns some descriptive aggregate data about the carrier-related statistics for a pair of airports.
 */
class AggregateCarrierStatistics extends Endpoint implements GetRequest
{
    public function __construct()
    {
        parent::__construct('/aggregate_carrier_statistics/{airport_1_code}/{airport_2_code}');
    }

    /**
     * Responds to a GET request to this resource.
     *
     * @param array $path_args Any path arguments provided, with the same name as declared by the URI used to construct
     * the endpoint which implements this interface.
     * @param array $args A string-indexed list of arguments provided by the client.
     *
     * @return Response A response to this request. This contains both a response code, and an array of data to send
     * back to the client.
     */
    public function get(array $path_args, array $args): Response
    {
        $data = $this->getData($path_args['airport_1_code'], $path_args['airport_2_code'], $args['carrier_code'] ?? '');

        $carrier_values = [];
        $late_aircraft_values = [];

        foreach ($data as $item) {
            $carrier_values[] = $item['carrier'];
            $late_aircraft_values[] = $item['late_aircraft'];
        }

        return new Response(
            200,
            [
                'carrier' => $this->computeStats($carrier_values),
                'late_aircraft' => $this->computeStats($late_aircraft_values)
            ]
        );
    }

    private function computeStats(array $values): array
    {
        $stats = [];
        $stats['average'] = array_sum($values) / count($values);
        $stats['standard_deviation'] = $this->standard_deviation($values);
        $stats['median'] = $this->median($values);

        return $stats;
    }

    function median($values): float
    {
        sort($values);
        $c = count($values);
        if ($c % 2 == 0) {
            return ($values[$c / 2] + $values[($c / 2) - 1]) / 2;
        } else {
            return $values[$c / 2];
        }
    }

    function standard_deviation($aValues): float
    {
        $fMean = array_sum($aValues) / count($aValues);
        $fVariance = 0.0;
        foreach ($aValues as $i)
        {
            $fVariance += pow($i - $fMean, 2);

        }
        $size = count($aValues) - 1;
        return (float) sqrt($fVariance)/sqrt($size);
    }

    private function getData(string $airport_1_code, string $airport_2_code, string $carrier_code = ''): array
    {
        $carrier_where_clause = '';
        if (!empty($carrier_code)) {
            $carrier_where_clause = ' AND carrier_id = (SELECT id FROM carriers WHERE carrier_code = :carrier_code)';
        }
        $sql = "
SELECT carrier, late_aircraft
FROM statistics_delays
WHERE statistic_id IN (
  SELECT id
  FROM statistics
  WHERE airport_id IN (
    SELECT id
    FROM airports
    WHERE
          (airport_code = :airport_1_code
       OR airport_code = :airport_2_code)
    )" . $carrier_where_clause . "
  );
        ";
        $stmt = $this->getDb()->prepare($sql);
        $stmt->bindValue(':airport_1_code', $airport_1_code);
        $stmt->bindValue(':airport_2_code', $airport_2_code);
        $stmt->bindValue(':carrier_code', $carrier_code);
        $result = $stmt->execute();
        $data = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }
        return $data;
    }
}