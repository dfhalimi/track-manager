<?php

declare(strict_types=1);

namespace App\ProjectManagement\Presentation\Controller;

use App\ProjectManagement\Domain\Dto\AddTrackToProjectInputDto;
use App\ProjectManagement\Domain\Dto\RemoveTrackFromProjectInputDto;
use App\ProjectManagement\Domain\Dto\ReorderProjectTracksInputDto;
use App\ProjectManagement\Domain\Service\ProjectManagementDomainServiceInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;
use ValueError;

final class ProjectTrackController extends AbstractController
{
    public function __construct(
        private readonly ProjectManagementDomainServiceInterface $projectManagementDomainService
    ) {
    }

    #[Route(path: '/projects/{projectUuid}/tracks/add', name: 'project_management.presentation.tracks.add', methods: [Request::METHOD_POST])]
    public function addAction(Request $request, string $projectUuid): Response
    {
        try {
            $this->projectManagementDomainService->addTrackToProject(
                new AddTrackToProjectInputDto(
                    $projectUuid,
                    $request->request->getString('track_uuid')
                )
            );
            $this->addFlash('success', 'Track wurde zum Projekt hinzugefügt.');
        } catch (Throwable $throwable) {
            $this->addFlash('error', $throwable->getMessage());
        }

        return $this->redirectToRoute('project_management.presentation.show', ['projectUuid' => $projectUuid]);
    }

    #[Route(path: '/projects/{projectUuid}/tracks/{trackUuid}/remove', name: 'project_management.presentation.tracks.remove', methods: [Request::METHOD_POST])]
    public function removeAction(string $projectUuid, string $trackUuid): Response
    {
        try {
            $this->projectManagementDomainService->removeTrackFromProject(
                new RemoveTrackFromProjectInputDto($projectUuid, $trackUuid)
            );
            $this->addFlash('success', 'Track wurde aus dem Projekt entfernt.');
        } catch (Throwable $throwable) {
            $this->addFlash('error', $throwable->getMessage());
        }

        return $this->redirectToRoute('project_management.presentation.show', ['projectUuid' => $projectUuid]);
    }

    #[Route(path: '/projects/{projectUuid}/tracks/reorder', name: 'project_management.presentation.tracks.reorder', methods: [Request::METHOD_POST])]
    public function reorderAction(Request $request, string $projectUuid): Response
    {
        try {
            $orderedTrackUuids = json_decode($request->request->getString('ordered_track_uuids', '[]'), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($orderedTrackUuids)) {
                throw new ValueError('Track-Reihenfolge muss als Liste übergeben werden.');
            }

            $this->projectManagementDomainService->reorderProjectTracks(
                new ReorderProjectTracksInputDto(
                    $projectUuid,
                    array_values(
                        array_filter(
                            $orderedTrackUuids,
                            static fn (mixed $trackUuid): bool => is_string($trackUuid) && trim($trackUuid) !== ''
                        )
                    )
                )
            );

            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['message' => 'Projekt-Reihenfolge wurde gespeichert.']);
            }

            $this->addFlash('success', 'Projekt-Reihenfolge wurde gespeichert.');
        } catch (Throwable $throwable) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['message' => $throwable->getMessage()], Response::HTTP_BAD_REQUEST);
            }

            $this->addFlash('error', $throwable->getMessage());
        }

        return $this->redirectToRoute('project_management.presentation.show', ['projectUuid' => $projectUuid]);
    }
}
