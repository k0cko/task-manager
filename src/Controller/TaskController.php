<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TaskController extends AbstractController
{
    #[Route('/tasks', name: 'app_task')]
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
            'currentUser' => $currentUser,
        ]);
    }
}
