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
    public function index(): Response
    {
        return $this->render('task/index.html.twig', [
            'controller_name' => 'TaskController',
            'tasks' => $this->getUser()->getTasks(),
        ]);
    }

    #[Route('/create', name: 'app_task_create')]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function create(Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TaskType::class, new Task(), [
            'submit_button_label' => 'Create Task'
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $task = $form->getData();

            $task->setOwner($this->getUser());

            $task->setCreatedAt(new \DateTimeImmutable('now'));
            $task->setUpdatedAt(new \DateTimeImmutable('now'));

            $entityManager->persist($task);
            $entityManager->flush();

            return $this->redirectToRoute('app_task');
        }
        
        return $this->render('task/form.html.twig', [
            'controller_name' => 'TaskController',
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_task_edit', methods: ['GET', 'POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function edit(Request $request, EntityManagerInterface $entityManager, string $id): Response
    {
        $task = $entityManager->getRepository(Task::class)->find($id);

        if ($task->getOwner() !== $this->getUser()) {
            throw $this->createAccessDeniedException(
                'You are trying to delete a task for a different user'
            );
        }

        $form = $this->createForm(TaskType::class, $task, [
            'submit_button_label' => 'Edit Task'
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $task->setUpdatedAt(new \DateTimeImmutable('now'));
            
            $entityManager->persist($task);
            $entityManager->flush();

            return $this->redirectToRoute('app_task');
        }

        return $this->render('task/form.html.twig', [
            'controller_name' => 'TaskController',
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_task_delete', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function delete(EntityManagerInterface $entityManager, string $id): Response
    {
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
