<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/admin', name: 'app_category')]
class CategoryController extends AbstractController
{
    public function __construct(
        private ValidatorInterface $validator,
        private EntityManagerInterface $em,
        private CategoryRepository $repo,
        private SerializerInterface $ser
    ) {}

    #[Route('/categories', name: 'app_admin_categories', methods: 'GET')]
    public function index(): JsonResponse
    {
        return $this->json([
            'categories' => $this->repo->findAll(),
        ]);
    }

    #[Route('/category/show/{id<\d+>}', name: 'app_admin_category_show', methods: 'GET')]
    public function show(Category $cat): JsonResponse
    {
        return $this->json([
            'category' => $cat,
        ]);
    }

    #[Route('/category/create', name: 'app_admin_category_create', methods: 'POST')]
    public function create(Request $req): JsonResponse
    {
        $entity = $this->ser->deserialize($req->getContent(), Category::class, 'json');
        $errors = $this->validator->validate($entity);
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

        $entity->setAuthor($this->getUser());

        $this->em->persist($entity);
        $this->em->flush();

        return $this->json([
            'status' => 'OK',
            'category' => $entity
        ]);
    }

    #[Route('/category/update/{id<\d+>}', name: 'app_admin_category_update', methods: 'POST')]
    public function update(Request $req, int $id): JsonResponse
    {
        $cat = $this->repo->find($id);
        if ($cat === null) {
            return $this->json([
                'status' => 'KO',
                'errors' => "No category found by the id : $id!"
            ], 404);
        }
        $entity = $this->ser->deserialize($req->getContent(), Category::class, 'json');
        $errors = $this->validator->validate($entity);
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

        $cat->setDescreption($entity->getDescreption());
        $cat->setName($entity->getName());

        $this->em->flush();

        return $this->json([
            'status' => 'OK',
            'category' => $cat
        ]);
    }

    #[Route('/category/delete/{id<\d+>}', name: 'app_admin_category_delete', methods: 'POST')]
    public function delete(int $id): JsonResponse
    {
        $cat = $this->repo->find($id);
        if ($cat === null) {
            return $this->json([
                'status' => 'KO',
                'errors' => "No category found by the id : $id!"
            ], 404);
        }

        $this->em->remove($cat);
        $this->em->flush();

        return $this->json([
            'status' => 'OK',
            'category' => $cat
        ]);
    }
}
