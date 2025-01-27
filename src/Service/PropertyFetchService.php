<?php

namespace App\Service;

use App\Entity\PropertyInfo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class PropertyFetchService{
    private EntityManagerInterface $entityManager;
    private $locations;
    private $synonyms;

    public function __construct(EntityManagerInterface $entityManager){
        $this->entityManager = $entityManager;

        // array cotaining locations that are nearby each other
        $this->locations = [
            'New York' => ['Brooklyn', 'Queens', 'Jersey City'],
            'Paris' => ['Versailles', 'Rouen', 'Troyes'],
            'London' => ['Cambridge', 'Margate', 'Brighton']
        ];

        // array containing the synonyms of each property type
        $this->synonyms = [
            'Apartment' => ['Apartment', 'Flat', 'Penthouse'],
            'House' => ['House'],
            'Villa' => ['Villa', 'Palace'],
            'Condo' => ['Condo', 'Condominium'],
            'Land' => ['Land'],
            'Buiding' => ['Building']
        ];
    }

    public function getProperties($conditionData): Response
    {
        $repo = $this->entityManager->getRepository(PropertyInfo::class);

        $properties = null;

        $location_conditions = [];

        // If there are no conditions, show all properties
        if ($conditionData == null){
            $properties = $repo->findAll();
        }
        // Get properties according to conditions
        else{
            $queryBuilder = $repo->createQueryBuilder('p');
            
            // get the property type info taking multiple types and synonyms into consideration
            $types = $conditionData["type"];
            if (!empty($types)){
                $queryBuilder
                    ->andWhere('p.type IN (:types)')
                    ->setParameter("types", $this->getSynonymOrigin($this->synonyms, $types));
            }

            // get location info taking multiple locations and nearby locations into consideration
            $location_conditions = $conditionData["location"];
            if (!empty($location_conditions)){
                $queryBuilder
                    ->andWhere("p.location IN (:locations)")
                    ->setParameter("locations", $this->getNearbyLocations($this->locations, $location_conditions));
            }

            // get price info
            $price = $conditionData["price"];
            if (!empty($price)){
                $queryBuilder
                    ->andWhere("p.price BETWEEN :price1 AND :price2")
                    ->setParameter("price1", $price - 50000)
                    ->setParameter('price2', $price + 50000);
            }

            // get room info
            $room = $conditionData["bedroom_number"];
            if (!empty($room)){
                $queryBuilder
                    ->andWhere("p.bedrooms BETWEEN :val1 AND :val2") // between room - 1 and room + 1 for deviation
                    ->setParameter("val1", $room - 1)
                    ->setParameter("val2", $room + 1);

            }
            
            $query = $queryBuilder->getQuery();
            $properties = $query->getResult();
        }

        // Map the results into an array to be parsed as json
        $propertyData = array_map(function(PropertyInfo $p) use ($location_conditions){
            $nearby = false;
            if (!empty($location_conditions) && !in_array($p->getLocation(), $location_conditions))
                $nearby = true;

            return [
                'id' => $p->getId(),
                'type' => $p->getType(),
                'bedrooms' => $p->getBedrooms(),
                'location' => $p->getLocation(),
                'price' => $p->getPrice(),
                'nearby' => $nearby
            ];
        }, $properties);

        return new JsonResponse($propertyData);        
    }

    function getNearbyLocations($locations, $location_conditions): array {
        $results = [];
        foreach ($location_conditions as $currentLocation){
            // Convert current location to lowercase for case-insensitive comparison
            $currentLocation = strtolower($currentLocation);
        
            // add current location to results
            array_push($results, $currentLocation);
        
            foreach ($locations as $mainLocation => $subLocations) {
                // Convert main location to lowercase
                $mainLocationLower = strtolower($mainLocation);
        
                // Convert sub-locations to lowercase
                $subLocationsLower = array_map('strtolower', $subLocations);
        
                // Check if the current location matches the main location or any sub-location (case-insensitive)
                if ($mainLocationLower === $currentLocation || in_array($currentLocation, $subLocationsLower)) {
                    array_push($results, array_merge($results, [$mainLocation], $subLocations));
                }
            }
        }

        // return an array with unique location values
        return array_unique($this->flattenArray($results));
    }

    function getSynonymOrigin($synonyms, $types): array {
        $origins = [];
        foreach ($types as $type){
            foreach ($synonyms as $origin => $synonym_array){
                // add the original word for every synonym into the $origins array taking case-sensitivity and plural into consideration
                if (in_array(strtolower(rtrim($type, 's')), array_map('strtolower', $synonym_array))){
                    array_push($origins, $origin);
                }
            }
        }
        return $origins;
    }
    
    function flattenArray(array $array): array{
        $result = [];
        array_walk_recursive($array, function ($value) use (&$result) {
            $result[] = $value;
        });
        return $result;
    }

}
