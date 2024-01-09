<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\UserService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractController
{

    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function register(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            $result = $this->userService->registerUser($data);
        } catch (\Exception $e) {
            // Catch any exception or specific exception types you want to handle
            $errorMessage = $e->getMessage();

            return new JsonResponse(['success' => false, 'error' => $errorMessage], Response::HTTP_BAD_REQUEST);
        }

        if ($result['success']) {
            return new JsonResponse($result, Response::HTTP_CREATED);
        } else {
            return new JsonResponse($result, Response::HTTP_BAD_REQUEST);
        }


    }

    public function login(
        Request $request,
        UserPasswordEncoderInterface $passwordEncoder,
        JWTTokenManagerInterface $JWTManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // Assuming you have a UserRepository or UserProvider to load the user by email
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['email' => $data['email']]);

        if (!$user || !$passwordEncoder->isPasswordValid($user, $data['password'])) {
            return new JsonResponse(['message' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }

        $token = $JWTManager->create($user);

        return new JsonResponse(['token' => $token]);
    }

    public function refresh(JWTTokenManagerInterface $JWTManager): JsonResponse
    {
        $newToken = $JWTManager->refresh($this->getUser());

        return new JsonResponse(['token' => $newToken]);
    }

    public function me()
    {
        $user = $this->getUser();

        return $this->json([
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
        ]);
    }
}
