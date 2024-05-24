<?php

declare(strict_types=1);

namespace BLInc\Controller;

use BLInc\Managers\DoorManager;
use JMS\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class DoorController
{
  private DoorManager $doorManager;

  private ValidatorInterface $validator;

  private Constraint $doorConstraint;

  public function __construct(DoorManager $doorManager, ValidatorInterface $validator)
  {
    $this->doorManager = $doorManager;
    $this->validator = $validator;
    $this->doorConstraint = new Assert\Collection([
      'fields' => [],
    ]);
  }

  public function getDoors(Request $request): Response
  {
    $doors = $this->doorManager->findAll();

    return new JsonResponse(['items' => $doors, 'count' => count($doors)]);
  }

  public function postDoor(SerializerInterface $serializer, UrlGeneratorInterface $urlGenerator, Request $request): Response
  {
    $content = $request->getContent();

    $door = json_decode($content, true, JSON_THROW_ON_ERROR);

    if (!is_array($door)) {
      return new JsonResponse(array(array('message' => 'Request must contain a hash or properties.')), 400);
    }

    $violations = $this->validator->validateValue($door, $this->doorConstraint, 'new');

    if (count($violations)) {
      return new Response(
        $serializer->serialize($violations, 'json'),
        400,
        array('Content-Type' => 'application/json')
      );
    }

    $doorId = $this->doorManager->create($door);

    $response = new JsonResponse();
    $response->setStatusCode(201);
    $response->headers->set('Location', $urlGenerator->generate('get_door', array('id' => $doorId)));

    return $response;
  }
}
