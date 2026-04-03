<?php

declare(strict_types=1);

namespace App\ActivityHistory\Presentation\Controller;

use App\ActivityHistory\Presentation\Service\ActivityHistoryPresentationServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ActivityHistoryController extends AbstractController
{
    public function __construct(
        private readonly ActivityHistoryPresentationServiceInterface $activityHistoryPresentationService
    ) {
    }

    #[Route(path: '/tracks/{trackUuid}/history', name: 'activity_history.presentation.track_modal', methods: ['GET'])]
    public function trackModalAction(string $trackUuid): Response
    {
        return $this->render('@activityhistory.presentation/_modal_content.html.twig', [
            'view' => $this->activityHistoryPresentationService->buildTrackHistoryModalViewDto($trackUuid),
        ]);
    }

    #[Route(path: '/projects/{projectUuid}/history', name: 'activity_history.presentation.project_modal', methods: ['GET'])]
    public function projectModalAction(string $projectUuid): Response
    {
        return $this->render('@activityhistory.presentation/_modal_content.html.twig', [
            'view' => $this->activityHistoryPresentationService->buildProjectHistoryModalViewDto($projectUuid),
        ]);
    }
}
