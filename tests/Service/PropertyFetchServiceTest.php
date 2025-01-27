<?php
namespace App\Tests\Service;

use PHPUnit\Framework\TestCase;
use App\Entity\PropertyInfo;
use App\Service\PropertyFetchService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query;

class PropertyFetchServiceTest extends TestCase
{
    // Function to create mocks for PropertyInfo Entities
    private function createPropertyInfoMock($id, $type, $bedrooms, $location, $price)
    {
        $property = $this->createMock(PropertyInfo::class);
        $property->method('getId')->willReturn($id);
        $property->method('getType')->willReturn($type);
        $property->method('getBedrooms')->willReturn($bedrooms);
        $property->method('getLocation')->willReturn($location);
        $property->method('getPrice')->willReturn($price);

        return $property;
    }

    // Function to compare actual wih expected values
    private function assertPropertyData(array $actual, $id, $type, $bedrooms, $location, $price)
    {
        $this->assertEquals($id, $actual['id']);
        $this->assertEquals($type, $actual['type']);
        $this->assertEquals($bedrooms, $actual['bedrooms']);
        $this->assertEquals($location, $actual['location']);
        $this->assertEquals($price, $actual['price']);
    }

    // Test for the getProperties method of the PropertyFetchService class
    public function testGetProperties()
    {
        // Mock the dependencies
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(EntityRepository::class);
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(Query::class);

        // Create property mocks
        $property1 = $this->createPropertyInfoMock(1, 'Apartment', 3, 'Paris', 500000);
        $property2 = $this->createPropertyInfoMock(2, 'House', 4, 'New York', 750000);
        $property3 = $this->createPropertyInfoMock(3, 'Apartment', 1, 'Troyes', 130000);

        // Configure repository and query mocks
        $repo->method('findAll')->willReturn([$property1, $property2, $property3]);
        $repo->method('createQueryBuilder')->willReturn($queryBuilder);
        $entityManager->method('getRepository')->willReturn($repo);
        $queryBuilder->method('select')->willReturnSelf();
        $queryBuilder->method('from')->willReturnSelf();
        $queryBuilder->method('andWhere')->willReturnSelf();
        $queryBuilder->method('setParameter')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn($query);

        // Create service
        $service = new PropertyFetchService($entityManager);

        // Test data
        $tests = [
            // Test 1: No conditions (all properties should show up)
            [
                'conditions' => null,
                'expected' => [$property1, $property2, $property3],
            ],
            // Test 2: Location condition + Nearby location check
            [
                'conditions' => ['type' => [], 'price' => null, 'bedroom_number' => null, 'location' => ['Troyes']],
                'expected' => [$property1, $property3],
            ],
            // Test 3: Price condition
            [
                'conditions' => ['type' => [], 'price' => 100000, 'bedroom_number' => null, 'location' => []],
                'expected' => [$property3],
            ],
            // Test 4: Bedroom number condition
            [
                'conditions' => ['type' => [], 'price' => null, 'bedroom_number' => 4, 'location' => []],
                'expected' => [$property2],
            ],
            // Test 5: Type condition
            [
                'conditions' => ['type' => ['House'], 'price' => null, 'bedroom_number' => null, 'location' => []],
                'expected' => [$property2],
            ],
            // Test 6: Type Synonym condition + plural type
            [
                'conditions' => ['type' => ['Flats'], 'price' => null, 'bedroom_number' => null, 'location' => []],
                'expected' => [$property1, $property3],
            ],
            // Test 7: Multiple Types condition with Synonyms and Plural
            [
                'conditions' => ['type' => ['Flats', 'House'], 'price' => null, 'bedroom_number' => null, 'location' => []],
                'expected' => [$property1, $property2, $property3],
            ],
            // Test 8: Multiple Locations
            [
                'conditions' => ['type' => [], 'price' => null, 'bedroom_number' => null, 'location' => ['Paris', 'New York']],
                'expected' => [$property1, $property2, $property3],
            ]
        ];

        $query->method('getResult')->willReturnOnConsecutiveCalls(
            $tests[1]['expected'],
            $tests[2]['expected'],
            $tests[3]['expected'],
            $tests[4]['expected'],
            $tests[5]['expected'],
            $tests[6]['expected'],
            $tests[7]['expected'],
        );

        foreach ($tests as $test) {
            $response = $service->getProperties($test['conditions']);

            // Assert that the response is a JsonResponse
            $this->assertInstanceOf(JsonResponse::class, $response);

            // Decode the response content
            $data = json_decode($response->getContent(), true);

            // Assert the count matches the expected number of properties
            $this->assertCount(count($test['expected']), $data);

            // Assert each property's data
            foreach ($data as $index => $propertyData) {
                $expectedProperty = $test['expected'][$index];
                $this->assertPropertyData(
                    $propertyData,
                    $expectedProperty->getId(),
                    $expectedProperty->getType(),
                    $expectedProperty->getBedrooms(),
                    $expectedProperty->getLocation(),
                    $expectedProperty->getPrice()
                );
            }
        }
    }
}
