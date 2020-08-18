<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Response;

class UserController extends AbstractController
{
    /**
     * @Route("/user", name="user", methods={"POST"})
     */
    public function index(Request $request, ValidatorInterface $validator)
    {
        $data = new User();

        $response = new Response();

        $userData = json_decode($request->getContent(), true);
        $username = empty($userData['username']) ? '' : $userData['username'];
        $password = empty($userData['password']) ? '' : $userData['password'];

        if (empty($username) || empty($password)) {
            return $this->json([
                'message' => 'Please fill all fields',
                'password' => $password,
                'user' => $username,
            ]);
        }

        $data->setUsername($username);
        $data->setPassword(password_hash($password, PASSWORD_DEFAULT));
        $data->setRegisterDate(new \DateTime('now'));
        $data->setLastLoginDate(new \DateTime('now'));

        $doctrine = $this->getDoctrine()->getManager();

        $userRepository = $doctrine->getRepository(\App\Entity\User::class);
        $user = $userRepository->findOneBy(['username' => $data->getUsername()]);

        if ((bool)$user) {
            if (password_verify($password, $user->getPassword())) {

                $user->setLastLoginDate(new \DateTime('now'));
                $doctrine->persist($user);
                $doctrine->flush();

                $response->setContent(json_encode([
                    'message' => 'Success, you are logged!',
                    'canLogin' => true
                ]));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            }
            $response->setContent(json_encode([
                'message' => 'User with this name already exists!',
                'canLogin' => false
            ]));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
        $doctrine->persist($data);
        $doctrine->flush();

        $response->setContent(json_encode([
            'message' => 'You are successfully registered!',
            'canLogin' => true
        ]));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}
