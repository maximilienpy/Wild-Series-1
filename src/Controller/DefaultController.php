<?php

namespace App\Controller;

use App\Repository\ProgramRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DefaultController extends AbstractController
{
    /**
     * @Route("/", name="app_index")
     */
    public function index(ProgramRepository $programRepository): Response
    {
        $programs = $programRepository->findBy(
            array(),
            ['id' => 'DESC'],
            3
        );
        
        return $this->render('default/index.html.twig', ["programs" => $programs]);
    }

    /**
     * @Route("/my-profile", name="app_profile")
     */
    public function profile(): Response
    {
        $user = $this->getUser();
        return $this->render('default/my-profile.html.twig', ["user" => $user]);
    }
}
