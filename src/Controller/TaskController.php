<?php

namespace App\Controller;

use App\Entity\Task;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\SerializerInterface;

class TaskController extends AbstractController
{
    private $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    #[Route('/api/tasks', name: 'get_tasks', methods: ['GET'])]
    public function getTasks(Request $request, EntityManagerInterface $entityManager, SerializerInterface $serializer): JsonResponse
    {
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 10);
        $offset = ($page - 1) * $limit;

        $searchTerm = $request->query->get('search', '');

        $queryBuilder = $entityManager->getRepository(Task::class)->createQueryBuilder('t');

        if ($searchTerm) {
            $queryBuilder->where('t.title LIKE :searchTerm OR t.description LIKE :searchTerm')
                         ->setParameter('searchTerm', '%' . $searchTerm . '%');
        }

        $tasks = $queryBuilder->setFirstResult($offset)
                              ->setMaxResults($limit)
                              ->getQuery()
                              ->getResult();

        $json = $serializer->serialize($tasks, 'json', ['groups' => 'task:read']);

        return new JsonResponse($json, JsonResponse::HTTP_OK, [], true);
    }

    #[Route('/api/task/{id}', name: 'get_task', methods: ['GET'])]
    public function getTask(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $task = $entityManager->getRepository(Task::class)->find($id);

        if (!$task) {
            return new JsonResponse(['message' => 'Task not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        return new JsonResponse($task, JsonResponse::HTTP_OK);
    }

    #[Route('/api/task', name: 'create_task', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager): JsonResponse
{
    $data = json_decode($request->getContent(), true);

    $task = new Task();
    $task->setTitle($data['title'])
         ->setDescription($data['description'])
         ->setStatus($data['status'] ?? 'todo')
         ->setCreatedAt(new \DateTime());

   
    $errors = $this->validator->validate($task);
    if (count($errors) > 0) {
        return new JsonResponse(['errors' => (string) $errors], JsonResponse::HTTP_BAD_REQUEST);
    }

    
    $entityManager->persist($task);
    $entityManager->flush();

    return new JsonResponse($task, JsonResponse::HTTP_CREATED);
}


    #[Route('/api/task/{id}', name: 'update_task', methods: ['PUT'])]
    public function update(int $id, Request $request, EntityManagerInterface $entityManager): JsonResponse
{
    $task = $entityManager->getRepository(Task::class)->find($id);

    if (!$task) {
        return new JsonResponse(['message' => 'Task not found'], JsonResponse::HTTP_NOT_FOUND);
    }

    $data = json_decode($request->getContent(), true);

    $task->setTitle($data['title'])
         ->setDescription($data['description'])
         ->setStatus($data['status'] ?? $task->getStatus())
         ->setModifiedAt(new \DateTime());

    $errors = $this->validator->validate($task);
    if (count($errors) > 0) {
        return new JsonResponse(['errors' => (string) $errors], JsonResponse::HTTP_BAD_REQUEST);
    }

    $entityManager->flush();

    return new JsonResponse($task, JsonResponse::HTTP_OK);
}


    #[Route('/api/task/{id}', name: 'delete_task', methods: ['DELETE'])]
    public function delete(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $task = $entityManager->getRepository(Task::class)->find($id);

        if (!$task) {
            return new JsonResponse(['message' => 'Task not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        $entityManager->remove($task);
        $entityManager->flush();

        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('/api/tasks/search', name: 'search_tasks', methods: ['GET'])]
public function search(Request $request): JsonResponse
{
    $query = $request->query->get('q');
    $tasks = $this->taskRepository->searchByTitleOrDescription($query);

    return $this->json($tasks);
}

}
