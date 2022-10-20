<?php


namespace App\Controller;


use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthController extends ApiController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/api/register", name="register", methods={"POST"})
     * @param Request $request
     * @param UserPasswordHasherInterface $userPasswordHasher
     * @return JsonResponse
     */
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher): JsonResponse
    {
        $request = $this->transformJsonBody($request);
        $username = $request->get('username');
        $password = $request->get('password');

        if (empty($username) || empty($password)) {
            return $this->respondValidationError("Invalid Username or Password");
        }


        $user = new User();
        $user->setUsername($username);
        $user->setPassword($userPasswordHasher->hashPassword($user, $password));
        $this->entityManager->persist($user);
        $this->entityManager-> flush();
        return $this->respondWithSuccess(sprintf('User %s successfully created', $user->getUsername()));
    }
}