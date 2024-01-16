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

    // Endpoint for user registration
    public function register(Request $request, ValidatorInterface $validator): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        try {
            // Call the UserService to register a new user
            $result = $this->userService->registerUser($data);
        } catch (\Exception $e) {
            // Handle any exceptions that may occur during user registration
            $errorMessage = $e->getMessage();

            return new JsonResponse(['success' => false, 'error' => $errorMessage], Response::HTTP_BAD_REQUEST);
        }

        // Return appropriate JSON response based on the registration result
        if ($result['success']) {
            return new JsonResponse($result, Response::HTTP_CREATED);
        } else {
            return new JsonResponse($result, Response::HTTP_BAD_REQUEST);
        }
    }

    // Endpoint for user login
    public function login(
        Request $request,
        UserPasswordEncoderInterface $passwordEncoder,
        JWTTokenManagerInterface $JWTManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // Find the user by email
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['email' => $data['username']]);

        // Check user credentials
        if (!$user || !$passwordEncoder->isPasswordValid($user, $data['password'])) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid credentials.'], Response::HTTP_UNAUTHORIZED);
        }

        // Check user if is active
        if (!$user->getActive()) {
            return new JsonResponse(['success' => false, 'message' => 'The user is not active, please contact Administrator'], Response::HTTP_UNAUTHORIZED);
        }

        $token = $JWTManager->create($user);

        return new JsonResponse(['token' => $token]);
    }

    // Endpoint to get the current authenticated user details
    public function me(Request $request)
    {
        $user = $this->getUser();

        $avatar = $user->getAvatar();

        // Ensure avatar URL is absolute
        if ($avatar && strpos($avatar, '://') === false) {
            $avatar = $request->getSchemeAndHttpHost() . '/' . ltrim($avatar, '/');
        }

        // Return user details as JSON response
        return $this->json([
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'avatar' => $avatar,
            'fullName' => $user->getFullName(),
            'photos' => $user->getPhotos(),
        ], 200, [], ['json_encode_options' => JSON_UNESCAPED_SLASHES]);
    }
}
