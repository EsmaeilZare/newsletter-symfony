<?php


namespace App\Controller;

use DateTimeImmutable;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Entity\User;
use App\Entity\News;

/**
 * @Route("/api", name="api_")
 */
class NewsController extends ApiController
{

    private function retrieve_articles(ManagerRegistry $doctrine, array $orderBy = null, $limit = null): array {
        $articles = $doctrine
            ->getRepository(News::class)
            ->findBy(
                array(),
                $orderBy,
                $limit
            );

        $data = [];

        foreach ($articles as $article) {
            $data[] = [
                'id' => $article->getId(),
                'title' => $article->getTitle(),
                'description' => $article->getContent(),
                'author' => $article->getAuthor()->getUserIdentifier(),
                'created_at' => $article->getCreatedAt(),
                'updated_at' => $article->getUpdatedAt(),
            ];
        }
        return $data;
    }


    /**
     * @Route("/news", name="news_index", methods={"GET"})
     */
    public function index(ManagerRegistry $doctrine): JsonResponse
    {
        $data = $this->retrieve_articles($doctrine);

        return $this->response($data);
    }


    /**
     * @Route("/latest_news", name="latest_news", methods={"GET"})
     */
    public function latest(ManagerRegistry $doctrine): JsonResponse
    {
        // in this api we return n(limit) last news ordered by the newest first
        $data = $this->retrieve_articles($doctrine, array(), array('updatedAt'=>'DESC'), 10);

        return $this->response($data);
    }


    /**
     * @Route("/news", name="news_new", methods={"POST"})
     */
    public function new(ManagerRegistry $doctrine, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        $request = $this->transformJsonBody($request);
        $entityManager = $doctrine->getManager();
        $now = new DateTimeImmutable();

        $article = new News();
        $article->setTitle($request->get('title'));
        $article->setContent($request->get('content'));
        $article->setCreatedAt($now);
        $article->setUpdatedAt($now);
        $article->setAuthor($user);

        $entityManager->persist($article);
        $entityManager->flush();

        return $this->respondWithSuccess('Created new article (news) successfully with id ' . $article->getId());
    }

    /**
     * @Route("/news/{id}", name="news_show", methods={"GET"})
     */
    public function show(ManagerRegistry $doctrine, int $id): JsonResponse
    {
        $article = $doctrine->getRepository(News::class)->find($id);

        if (!$article) {

            return $this->respondNotFound('No article found for id' . $id);
        }

        $data = [
            'id' => $article->getId(),
            'title' => $article->getTitle(),
            'description' => $article->getContent(),
            'author' => $article->getAuthor()->getUserIdentifier(),
            'created_at' => $article->getCreatedAt(),
            'updated_at' => $article->getUpdatedAt(),
        ];

        return $this->response($data);
    }

    /**
     * @Route("/news/{id}", name="news_edit", methods={"PATCH"})
     */
    public function edit(ManagerRegistry $doctrine, Request $request, int $id, #[CurrentUser] ?User $user): Response
    {
        $request = $this->transformJsonBody($request);
        $entityManager = $doctrine->getManager();
        $now = new DateTimeImmutable();
        $article = $entityManager->getRepository(News::class)->find($id);

        if (!$article) {
            return $this->respondNotFound('No article found for id ' . $id);
        }

        if ($article->getAuthor() != $user){
            return $this->respondUnauthorized("You are not authorized to do this action");
        }

        $article->setTitle($request->get('title') ?? $article->getTitle());
        $article->setContent($request->get('content') ?? $article->getContent());
        $article->setUpdatedAt($now);
        $entityManager->flush();

        return $this->respondWithSuccess('article (news) with id ' . $article->getId() . ' edited successfully');
    }

    /**
     * @Route("/news/{id}", name="news_delete", methods={"DELETE"})
     */
    public function delete(ManagerRegistry $doctrine, int $id, #[CurrentUser] ?User $user): Response
    {
        $entityManager = $doctrine->getManager();
        $article = $entityManager->getRepository(News::class)->find($id);

        if (!$article) {
            return $this->respondNotFound('No article found for id' . $id);
        }

        if ($article->getAuthor() != $user){
            return $this->respondUnauthorized("You are not authorized to do this action");
        }

        $entityManager->remove($article);
        $entityManager->flush();

        return $this->respondWithSuccess('Deleted a news article successfully with id ' . $id);
    }
}
