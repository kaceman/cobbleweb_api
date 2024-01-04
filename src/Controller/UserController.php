<?php

namespace App\Controller;

use App\Service\UserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @Route("/api")
 */
class UserController extends AbstractController
{

    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @Route("/users/register", name="user_register", methods={"POST"})
     */
    public function register(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $result = $this->userService->registerUser($data);

        if ($result['success']) {
            return new JsonResponse(['message' => $result['message']], Response::HTTP_CREATED);
        } else {
            return new JsonResponse(['errors' => $result['errors']], Response::HTTP_BAD_REQUEST);
        }
    }
}
