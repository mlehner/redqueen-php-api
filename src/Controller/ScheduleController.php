<?php

declare(strict_types=1);

namespace BLInc\Controller;

use BLInc\Managers\DoorManager;
use BLInc\Managers\ScheduleManager;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Routing\Annotation\Route;

final class ScheduleController
{
    private ScheduleManager $scheduleManager;

    private DoorManager $doorManager;

    private ValidatorInterface $validator;

    private SerializerInterface $serializer;

    private UrlGeneratorInterface $urlGenerator;

    private Constraint $constraint;

    public function __construct(ScheduleManager $scheduleManager, DoorManager $doorManager, ValidatorInterface $validator, SerializerInterface $serializer, UrlGeneratorInterface $urlGenerator)
    {
        $this->scheduleManager = $scheduleManager;
        $this->doorManager = $doorManager;
        $this->validator = $validator;
        $this->serializer = $serializer;
        $this->urlGenerator = $urlGenerator;
        $this->constraint = new Assert\Collection([
            'fields' => [
                'name' => new Assert\NotBlank(),
                'mon' => new Assert\Type(['type' => 'boolean']),
                'tue' => new Assert\Type(['type' => 'boolean']),
                'wed' => new Assert\Type(['type' => 'boolean']),
                'thu' => new Assert\Type(['type' => 'boolean']),
                'fri' => new Assert\Type(['type' => 'boolean']),
                'sat' => new Assert\Type(['type' => 'boolean']),
                'sun' => new Assert\Type(['type' => 'boolean']),
                'startTime' => new Assert\Time(),
                'endTime' => new Assert\Time(),
                'authenticationMode' => new Assert\Choice(['choices' => ['card_pin' => 'Card & Pin', 'card' => 'Card Only']]),
                'doors' => [
                    new Assert\Count(['min' => 1]),
                    new Assert\All([
                        new Assert\Collection([
                            'fields' => [
                                'id' => [
                                    new Assert\NotBlank(),
                                    // @TODO valid Door
                                ],
                            ],
                        ]),
                    ]),
                ],
            ],
        ]);
    }

    #[Route(path: '/api/schedules', name: 'get_schedules', methods: Request::METHOD_GET)]
    public function getSchedules(): Response
    {
        $schedules = $this->scheduleManager->findAll();
        $schedules = $this->injectDoors($schedules);

        return new JsonResponse(['items' => $schedules, 'count' => count($schedules)]);
    }

    #[Route(path: '/api/schedules/{id}', name: 'get_schedule', methods: Request::METHOD_GET)]
    public function getSchedule(string $id): Response
    {
        $schedule = $this->scheduleManager->find($id);

        if (!is_array($schedule)) {
            throw new NotFoundHttpException();
        }

        $schedules = $this->injectDoors([$schedule]);

        return new JSONResponse($schedules[0]);
    }

    #[Route(path: '/api/schedules', name: 'post_schedule', methods: Request::METHOD_POST)]
    public function postSchedule(Request $request): Response
    {
        $content = $request->getContent();

        $schedule = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse([['message' => 'Failed to parse request.']], 400);
        }

        if (!is_array($schedule)) {
            return new JsonResponse([['message' => 'Request must contain a hash or properties.']], 400);
        }

        $violations = $this->validator->validateValue($schedule, $this->constraint, 'new');

        if (count($violations)) {
            return new Response(
                $this->serializer->serialize($violations, 'json'),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $schedule_id = $this->scheduleManager->create($schedule);

        $response = new JsonResponse();
        $response->setStatusCode(201);
        $response->headers->set('Location', $this->urlGenerator->generate('get_schedule', ['id' => $schedule_id]));

        return $response;
    }

    #[Route(path: '/api/schedules/{id}', name: 'put_schedule', methods: Request::METHOD_PUT)]
    public function putSchedule(Request $request, string $id): Response
    {
        $schedule = $this->scheduleManager->find($id);

        if (!is_array($schedule)) {
            throw new NotFoundHttpException();
        }

        $content = $request->getContent();

        $schedule = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse([['message' => 'Failed to parse request.']], 400);
        }

        if (!is_array($schedule)) {
            return new JsonResponse([['message' => 'Request must contain a hash or properties.']], 400);
        }

        $constraints = clone $this->constraint;
        $constraints->allowMissingFields = true;

        $violations = $this->validator->validateValue($schedule, $constraints, 'edit');

        if (count($violations)) {
            return new Response(
                $this->serializer->serialize($violations, 'json'),
                400,
                ['Content-Type' => 'application/json']
            );
        }

        $this->scheduleManager->update($id, $schedule);

        $response = new JsonResponse();
        $response->setStatusCode(201);
        $response->headers->set('Location', $this->urlGenerator->generate('get_schedule', ['id' => $id]));

        return $response;
    }

    private function injectDoors(array $schedules): array
    {
        $doors = $this->doorManager->findBySchedules(array_map(function (array $schedule) {
            return $schedule['id'];
        }, $schedules));

        $doorsByScheduleId = [];

        foreach ($doors as $door) {
            if (!isset($doorsByScheduleId[$door['schedule_id']])) {
                $doorsByScheduleId[$door['schedule_id']] = [];
            }

            $doorsByScheduleId[$door['schedule_id']][] = $door;
        }

        return array_map(function (array $schedule) use ($doorsByScheduleId) {
            $scheduleDoors = $doorsByScheduleId[$schedule['id']] ?? [];

            $schedule['doors'] = array_map(function (array $door) {
                return $this->normalizeDoor($door);
            }, $scheduleDoors);

            return $schedule;
        }, $schedules);
    }

    private function normalizeDoor(array $door): array
    {
        return [
            'id' => $door['id'],
            'name' => $door['name'],
        ];
    }
}
