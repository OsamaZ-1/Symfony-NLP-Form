<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\PropertyFetchService;
use App\Service\PythonService;
use App\Form\Type\NLPForm;

class SearchFormController extends AbstractController{

    private PropertyFetchService $pfs;
    private PythonService $nlps;
    private $interpreterPath;
    private $scriptPath;
    private $scriptName;

    public function __construct(PropertyFetchService $pfs, PythonService $nlps){
        $this->pfs = $pfs;
        $this->nlps = $nlps;
        $this->interpreterPath = ".\..\python\python.exe";
        $this->scriptPath = ".\..\python\python scripts\\";
        $this->scriptName = "nlpParser.py";
    }

    #[Route("/", "nlp_search")]
    public function HousingSearch(Request $request): Response{

        // create form and handle submissions
        $form = $this->createForm(NLPForm::class);
        $form->handleRequest($request);
        
        $parsedSentenceData = null;
        if ($form->isSubmitted() && $form->isValid()){
            $searchTxt = $form->get("search")->getData();

            $parsedSentenceJson = $this->nlps->runScript($this->interpreterPath, $this->scriptPath, $this->scriptName, [$searchTxt]);
            $parsedSentenceData = json_decode($parsedSentenceJson->getContent(), true);
            // print_r($parsedSentenceData);
        }

        // fetch properties
        $jsonData = $this->pfs->getProperties($parsedSentenceData);
        $propertyData = json_decode($jsonData->getContent(), true);

        return $this->render("search_form.html.twig", ["form" => $form->createView(), "properties" => $propertyData]);
    }
}