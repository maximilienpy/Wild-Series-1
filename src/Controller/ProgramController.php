<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Season;
use App\Entity\Comment;
use App\Entity\Episode;
use App\Entity\Program;
use App\Service\Slugify;
use App\Form\CommentType;
use App\Form\ProgramType;
use Symfony\Component\Mime\Email;
use App\Form\SearchProgramFormType;
use App\Repository\CommentRepository;
use App\Repository\ProgramRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
/**
* @Route("/programs", name="program_")
*/
Class ProgramController extends AbstractController
{
    /**
     * The controller for the category add form
     *
     * @Route("/new", methods={"GET","POST"}, name="new")
     */
    public function new(Request $request, Slugify $slugify, MailerInterface $mailer) : Response
    {
       
        $program = new Program();
        $form = $this->createForm(ProgramType::class, $program);
        $form->handleRequest($request);
 
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $slug = $slugify->generate($program->getTitle());
            $program->setSlug($slug);
            $program->setOwner($this->getUser());
            $entityManager->persist($program);
            $entityManager->flush();

            $this->addFlash('success', 'La série a bien été ajoutée');

            $email = (new Email())
                ->from($this->getParameter('mailer_from'))
                ->to($this->getParameter('mailer_to'))
                ->subject('Une nouvelle série vient d\'être publiée !')
                ->html(
                    $this->renderView('program/newProgramEmail.html.twig', [
                    'program' => $program
                ])
            );

            $mailer->send($email);

            return $this->redirectToRoute('program_index');
        }
        // Render the form
        return $this->render('program/new.html.twig', [
            "form" => $form->createView(),
        ]);
    }

        /**
     * @Route("/", name="index")
     */
    public function index(Request $request, ProgramRepository $programRepository): Response
    {        
        $form = $this->createForm(SearchProgramFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $search = $form->getData()['search'];
            $programs = $programRepository->findLikeName($search);
        } else {
            $programs = $programRepository->findAll();
        }
        
        return $this->render(
            'program/index.html.twig',
            ['programs' => $programs,
            'form' => $form->createView(),
        ]
        );
    }

     /**
     * @Route("/{slug}", methods={"GET"}, name="show")
     */
    public function show(Program $program): Response
    {
        if (!$program) {
            throw $this->createNotFoundException(
                'No program found in program\'s table.'
            );
        }

        $seasons = $program->getSeasons();

        return $this->render('program/show.html.twig', [
            'program' => $program,
            'seasons' => $seasons
        ]);
    }

    /**
     * @Route("/{slug}/edit", name="edit", methods={"GET","POST"})
     * @ParamConverter("program", class="App\Entity\Program", options={"mapping": {"slug": "slug"}})
     */
    public function edit(Request $request, Program $program): Response
    {
        
        if (!($this->getUser() == $program->getOwner())) {
            // If not the owner, throws a 403 Access Denied exception
            throw new AccessDeniedException('Only the owner can edit the program!');
        }

        $form = $this->createForm(ProgramType::class, $program);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            $this->addFlash('success', 'La série a bien été modifiée');

            return $this->redirectToRoute('program_index');
        }

        return $this->render('program/edit.html.twig', [
            'program' => $program,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{program}/seasons/{season}", requirements={"season"="\d+"}, methods={"GET"}, name="season_show")
     */
    public function showSeason(Program $program, Season $season){
        
        $episodes = $season->getEpisodes();

        return $this->render('program/season_show.html.twig', [
            'program' => $program,
            'season' => $season,
            'episodes' => $episodes,
        ]);
    }

    /**
     * @Route("/{programId}/seasons/{seasonId}/episodes/{episodeId}", requirements={"season"="\d+"}, methods={"GET","POST"}, name="episode_show")
     * @ParamConverter("program", class="App\Entity\Program", options={"mapping": {"programId": "id"}})
     * @ParamConverter("season", class="App\Entity\Season", options={"mapping": {"seasonId": "id"}})
     * @ParamConverter("episode", class="App\Entity\Episode", options={"mapping": {"episodeId": "id"}})
     */
    public function showEpisode(Program $program, Season $season, Episode $episode, CommentRepository $commentRepository,Request $request)
    {    
        $user = $this->getUser();

        $comment = new Comment();
        $comment->setAuthor($user);
        $comment->setEpisode($episode);
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($comment);
            $entityManager->flush();
        }

        $comments = $commentRepository->findBy(
                ['episode' => $episode], 
                ['id' => 'DESC'],
                5
            );

        return $this->render('program/episode_show.html.twig', [
            'program' => $program,
            'season' => $season,
            'episode' => $episode,
            'form' => $form->createView(),
            'comments' => $comments
        ]);
    }
    
    /**
     * @Route("/{commentId}", name="comment_delete", methods={"DELETE"})
     * @ParamConverter("comment", class="App\Entity\Comment", options={"mapping": {"commentId": "id"}})
     */
    public function deleteComment(Comment $comment, Request $request): Response
    {
        if ($this->isCsrfTokenValid('delete' . $comment->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($comment);
            $entityManager->flush();
        }
        
          return $this->redirect($request->server->get('HTTP_REFERER'));
    }
}