<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class TaskController extends AbstractController
{
    #[Route('/', name: 'app_task')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $currentUser = $this->getUser();

        $form = $this->createForm(TaskType::class, new Task());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $task = $form->getData();

            $task->setOwner($currentUser);

            $task->setCreatedAt(new \DateTimeImmutable('now'));
            $task->setUpdatedAt(new \DateTimeImmutable('now'));

            $entityManager->persist($task);
            $entityManager->flush();
        }

        return $this->render('task/index.html.twig', [
            'controller_name' => 'TaskController',
            'form' => $form,
            'tasks' => $currentUser->getTasks(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_task_delete', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function delete(EntityManagerInterface $entityManager, string $id): Response {
        $task = $entityManager->getRepository(Task::class)->find($id);
        
        if (!$task) {
            throw $this->createNotFoundException(
                'No task found for id ' . $id
            );
        }

        if ($task->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException(
                'You are trying to delete a task for a different user'
            );
        }

        $entityManager->remove($task);
        $entityManager->flush();

        return $this->redirectToRoute('app_task');  
    }
}
