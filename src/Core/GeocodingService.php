<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Geocoding and road distance calculation service via OpenRouteService.
 *
 * Requires an API key defined in the ORS_API_KEY environment variable.
 * All HTTP requests are made with cURL.
 */
class GeocodingService
{
    private string $apiKey;
    private const GEOCODING_URL = 'https://api.heigit.org/pelias/v1';
    private const ROUTING_URL   = 'https://api.heigit.org/openrouteservice/v2/directions';

    /**
     * @throws \InvalidArgumentException If ORS_API_KEY is not defined in the environment
     */
    public function __construct()
    {
        $this->apiKey = $_ENV['ORS_API_KEY'] ?? '';

        if (empty($this->apiKey)) {
            throw new \InvalidArgumentException('ORS_API_KEY non définie dans .env');
        }
    }

    /**
     * Geocodes an address and returns its GPS coordinates.
     *
     * @param string $address Full address to geocode
     * @return array{latitude: float, longitude: float, label: string}|null
     */
    public function geocode(string $address): ?array
    {
        $url = self::GEOCODING_URL . '/search';

        $params = [
            'api_key' => $this->apiKey,
            'text'    => $address,
            'size'    => 1
        ];

        $response = $this->makeRequest($url . '?' . http_build_query($params));

        if ($response && isset($response['features'][0])) {
            $feature = $response['features'][0];
            return [
                'latitude'  => $feature['geometry']['coordinates'][1],
                'longitude' => $feature['geometry']['coordinates'][0],
                'label'     => $feature['properties']['label'] ?? $address
            ];
        }

        return null;
    }

    /**
     * Calculates the road distance between two GPS points.
     *
     * The ORS API expects coordinates as longitude,latitude (not lat,lon).
     *
     * @param float $lat1 Latitude of the starting point
     * @param float $lon1 Longitude of the starting point
     * @param float $lat2 Latitude of the destination
     * @param float $lon2 Longitude of the destination
     * @return float|null Distance in kilometres, or null if the calculation fails
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): ?float
    {
        $url = self::ROUTING_URL . '/driving-car';

        $params = [
            'api_key' => $this->apiKey,
            'start'   => "$lon1,$lat1",
            'end'     => "$lon2,$lat2"
        ];

        $response = $this->makeRequest($url . '?' . http_build_query($params));

        if ($response) {
            // JSON format (GET /v2/directions/{profile})
            if (isset($response['routes'][0]['summary']['distance'])) {
                return round($response['routes'][0]['summary']['distance'] / 1000, 2);
            }
            // GeoJSON format (GET /v2/directions/{profile}/geojson)
            if (isset($response['features'][0]['properties']['summary']['distance'])) {
                return round($response['features'][0]['properties']['summary']['distance'] / 1000, 2);
            }
        }

        return null;
    }

    /**
     * Calculates the road distance from Bordeaux to a delivery address.
     *
     * Fixed origin: Bordeaux (44.837789, -0.57918).
     *
     * @param string $deliveryAddress Street number and name
     * @param string $deliveryCity    City
     * @return float|null Distance in kilometres, or null if geocoding fails
     */
    public function getDistanceFromBordeaux(string $deliveryAddress, string $deliveryCity): ?float
    {
        $bordeauxLat = 44.837789;
        $bordeauxLon = -0.57918;

        $fullAddress = "$deliveryAddress, $deliveryCity, France";

        if (isset($_SESSION['geocode_cache'][$fullAddress])) {
            return $_SESSION['geocode_cache'][$fullAddress];
        }

        $location = $this->geocode($fullAddress);

        if (!$location) {
            return null;
        }

        $distance = $this->calculateDistance(
            $bordeauxLat,
            $bordeauxLon,
            $location['latitude'],
            $location['longitude']
        );

        if ($distance !== null) {
            $_SESSION['geocode_cache'][$fullAddress] = $distance;
        }

        return $distance;
    }

    /**
     * Performs an HTTP GET request and returns the decoded JSON body.
     *
     * @param string $url Full URL with query string parameters
     * @return array|null Decoded response, or null if the request fails (non-200 status)
     */
    private function makeRequest(string $url): ?array
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => ['Accept: application/json, application/geo+json']
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError) {
            return null;
        }

        if ($httpCode !== 200) {
            return null;
        }

        return json_decode($response, true) ?: null;
    }
}
