<?php

namespace App\Controller;

use App\Entity\MExpenses;
use App\Entity\Users;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Exception;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class UsersController extends AbstractController
{
    # Utils
    private $entityManager;
    private $serializer;
    # Messages
    private $message500 = 'Hubo un error en nuestro servidor, por favor intenta nuevamente y si el error persiste, contacte con un administrador.';

    public function __construct(EntityManagerInterface $em)
    {
        # Entity Manager
        $this->entityManager = $em;
        # Inyectamos Serializer en controlador
        $this->serializer = new Serializer(array(new ObjectNormalizer()), array(new JsonEncoder()));
    }

    /**
     * @Route("/users/newuser", name="users_newUser", methods={"POST", "OPTIONS"})
     */
    public function newUser(Request $request): JsonResponse
    {
        try {
            $params = $request->request->all();
            $U_name = $params['name'];
            $U_email = $params['email'];
            $U_salary = $params['salary'] ? $params['salary'] : 0;
            if (!$U_name || !$U_email) {
                return new JsonResponse([
                    'status' => false,
                    'message' => 'Te faltó ingresar un valor obligatorio',
                    'data' => [],
                ], 400);
            }

            $User = new Users();
            $User->setName($U_name);
            $User->setEmail($U_email);
            $User->setSalary($U_salary);

            $this->entityManager->persist($User);
            $this->entityManager->flush();

            $UserData = $this->entityManager->getRepository(Users::class)->findOneBy(['email' => $U_email]);
            $UserData = $this->serializer->normalize($UserData, 'json');

            return new JsonResponse([
                'status' => true,
                'message' => 'Ok',
                'data' => [$UserData],
            ], 200);
        } catch (\Throwable $th) {
            return new JsonResponse([
                'status' => false,
                'message' => $this->message500,
                'data' => [],
            ], 500);
        }
    }
    
}
