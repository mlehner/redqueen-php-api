<?php

declare(strict_types=1);

namespace BLInc\Controller;

use BLInc\Managers\ScheduleManager;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class ScheduleController
{
  private ScheduleManager $scheduleManager;

  private ValidatorInterface $validator;

  private SerializerInterface $serializer;

  private UrlGeneratorInterface $urlGenerator;

  private Constraint $constraint;

  public function __construct(ScheduleManager $scheduleManager, ValidatorInterface $validator, SerializerInterface $serializer, UrlGeneratorInterface $urlGenerator)
  {
    $this->scheduleManager = $scheduleManager;
    $this->validator = $validator;
    $this->serializer = $serializer;
    $this->urlGenerator = $urlGenerator;
    $this->constraint = new Assert\Collection(array(
      'fields' => array(
        'name' => new Assert\NotBlank(),
        'mon' => new Assert\Type(array('type' => 'boolean')),
        'tue' => new Assert\Type(array('type' => 'boolean')),
        'wed' => new Assert\Type(array('type' => 'boolean')),
        'thu' => new Assert\Type(array('type' => 'boolean')),
        'fri' => new Assert\Type(array('type' => 'boolean')),
        'sat' => new Assert\Type(array('type' => 'boolean')),
        'sun' => new Assert\Type(array('type' => 'boolean')),
        'startTime' => new Assert\Time(),
        'endTime' => new Assert\Time(),
      )
    ));
  }

  public function getSchedules(): Response
  {
    $schedules = $this->scheduleManager->findAll();

    return new JsonResponse(['items' => $schedules, 'count' => count($schedules)]);
  }

  public function getSchedule(int $id): Response
  {
    $schedule = $this->scheduleManager->find($id);

    if (!is_array($schedule)) {
      throw new NotFoundHttpException();
    }

    return new JSONResponse($schedule);
  }

  public function postSchedule(Request $request): Response
  {
    $content = $request->getContent();

    $schedule = json_decode($content, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
      return new JsonResponse(array(array('message' => 'Failed to parse request.')), 400);
    }

    if (!is_array($schedule)) {
      return new JsonResponse(array(array('message' => 'Request must contain a hash or properties.')), 400);
    }

    $violations = $this->validator->validateValue($schedule, $this->constraint, 'new');

    if (count($violations)) {
      return new Response(
        $this->serializer->serialize($violations, 'json'),
        400,
        array('Content-Type' => 'application/json')
      );
    }

    $schedule_id = $this->scheduleManager->create($schedule);

    $response = new JsonResponse();
    $response->setStatusCode(201);
    $response->headers->set('Location', $this->urlGenerator->generate('get_schedule', array('id' => $schedule_id)));

    return $response;
  }

  public function putSchedule(Request $request, int $id): Response
  {
    $schedule = $this->scheduleManager->find($id);

    if (!is_array($schedule)) {
      throw new NotFoundHttpException();
    }

    $content = $request->getContent();

    $schedule = json_decode($content, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
      return new JsonResponse(array(array('message' => 'Failed to parse request.')), 400);
    }

    if (!is_array($schedule)) {
      return new JsonResponse(array(array('message' => 'Request must contain a hash or properties.')), 400);
    }

    $constraints = clone $this->constraint;
    $constraints->allowMissingFields = true;

    $violations = $this->validator->validateValue($schedule, $constraints, 'edit');

    if (count($violations)) {
      return new Response(
        $this->serializer->serialize($violations, 'json'),
        400,
        array('Content-Type' => 'application/json')
      );
    }

    $this->scheduleManager->update($id, $schedule);

    $response = new JsonResponse();
    $response->setStatusCode(201);
    $response->headers->set('Location', $this->urlGenerator->generate('get_schedule', array('id' => $id)));

    return $response;
  }
}
