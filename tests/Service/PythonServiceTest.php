<?php

namespace App\Tests\Service;

use PHPUnit\Framework\TestCase;
use App\Service\PythonService;

class PythonServiceTest extends TestCase
{
    // Test for the python NLP functionality being run through the PythonService class
    public function testRunScriptSuccess()
    {
        // Specify the Python script and interpreter paths
        $interpreterPath = ".\python\python.exe";
        $scriptPath = '.\python\python scripts\\';
        $scriptName = "nlpParser.py";
        
        // All tests and their expected results
        $sentences = [
            // Test 1 -- Case with all conditions
            "Apartment in London for 100K with 3 bedrooms" => [
                'type' => ['Apartment'],
                'bedroom_number' => 3,
                'price' => 100000,
                'location' => ['London']
            ],

            // Test 2 -- Case with multiple Property Types + Plural
            "Apartments and Houses" => [
                'type' => ['Apartments', 'Houses'],
                'bedroom_number' => null,
                'price' => null,
                'location' => []
            ],

            // Test 3 -- Test for Millions
            "villa for 1.5M" => [
                'type' => ['villa'],
                'bedroom_number' => null,
                'price' => 1500000,
                'location' => []
            ],

            // Test 4 -- Multiple Locations
            "Flats in London and New York" => [
                'type' => ['Flats'],
                'bedroom_number' => null,
                'price' => null,
                'location' => ['London', 'New York']
            ],

            // Test 5 -- Price without symbols
            "House for 500000" => [
                'type' => ['House'],
                'bedroom_number' => null,
                'price' => 500000,
                'location' => []
            ],

            // Test 6 -- Price with separators
            "House for $1,300,000" => [
                'type' => ['House'],
                'bedroom_number' => null,
                'price' => 1300000,
                'location' => []
            ],
        ];

        foreach ($sentences as $sentence => $expectedOutput){
            // Create the PythonScript Service Object
            $process = new PythonService();

            // Run the process
            $output = $process->runScript($interpreterPath, $scriptPath, $scriptName, [$sentence]);

            // Parse the JSON output
            $output = json_decode($output->getContent(), true);

            // Assert that the output matches the expected output
            for ($i = 0; $i < count($expectedOutput['type']); ++$i)
                $this->assertEquals($expectedOutput['type'][$i], $output["type"][$i]);

            $this->assertEquals($expectedOutput['bedroom_number'], $output["bedroom_number"]);
            
            $this->assertEquals($expectedOutput['price'], $output["price"]);

            for ($i = 0; $i < count($expectedOutput['location']); ++$i)
                $this->assertEquals($expectedOutput['location'][$i], $output["location"][$i]);
        }
    }
}
