<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class PythonService{

    public function runScript($inerpreterPath, $path, $script, $arguments = []): Response
    {
        // Run the python process
        $scriptPath = $path . $script;
        $process = new Process(array_merge([$inerpreterPath, $scriptPath], $arguments));
        $process->run();

        // Check for errors
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        // Return the output
        $parsedData = json_decode($process->getOutput(), true);

        return new JsonResponse($parsedData);
    }
}
