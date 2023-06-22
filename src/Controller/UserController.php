<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/api')]
class UserController extends AbstractController
{
    public function __construct(
        private ValidatorInterface $validator,
        private EntityManagerInterface $em,
        private UserRepository $repo,
        private SerializerInterface $ser
    ) {}

    
    #[Route('/users', name: 'app_users', methods: 'GET')]
    public function index(): JsonResponse
    {
        return $this->json([
            'users' => $this->repo->findAll(),
            'user' => $this->getUser()
        ]);
    }
    
    #[Route('/user/create', name: 'app_user_create', methods: 'POST')]
    public function create(Request $req, UserPasswordHasherInterface $passwordHasher): JsonResponse
    {
        $user = $this->ser->deserialize($req->getContent(), User::class, 'json');
        $errors = $this->validator->validate($user);
        $parsedErrors = [];

        if (count($errors) > 0) {
            foreach ($errors as $error) {
                $parsedErrors[] = (string) $error;
            }
            
            return $this->json([
                'status' => 'KO',
                'errors' => $parsedErrors
            ], 400);
        }
        $hashed = $passwordHasher->hashPassword(
            $user,
            $user->getPlaintextPassword()
        );

        $user->setPassword($hashed);

        $this->em->persist($user);
        $this->em->flush();

        return $this->json([
            'status' => 'OK',
            'user' => $user
        ]);
    }
}