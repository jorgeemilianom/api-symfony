<?php

namespace App\Controller;

use App\Entity\MExpenses;
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

class ExpensesController extends AbstractController
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
     * @Route("/expenses", name="app_expenses")
     */
    public function index(): Response
    {
        return $this->render('expenses/index.html.twig', [
            'controller_name' => 'ExpensesController',
        ]);
    }

    /**
     * @Route("/expenses/getall", name="expenses_getAll")
     */
    public function getAll(): JsonResponse
    {
        try {
            $ExpensesList = $this->entityManager->getRepository(MExpenses::class)->findAll();
            $ExpensesList = $this->serializer->normalize($ExpensesList, 'json');

            return new JsonResponse([
                'status' => true,
                'message' => 'Ok',
                'data' => [$ExpensesList],
            ], 200);
        } catch (\Throwable $th) {
            return new JsonResponse([
                'status' => false,
                'message' => $this->message500,
                'data' => [],
            ], 500);
        }
    }

    /**
     * @Route("/expenses/newExpense", name="expenses_newExpense", methods={"POST", "OPTIONS"})
     */
    public function newExpense(Request $request): JsonResponse
    {
        try {
            $params = $request->request->all();
            $E_name = $params['name'];
            $E_type = $params['type'];
            $E_amount = $params['amount'];

            if (!$E_name || !$E_amount) {
                return new JsonResponse([
                    'status' => false,
                    'message' => 'Te faltó ingresar un valor obligatorio',
                    'data' => [],
                ], 400);
            }

            $Expense = new MExpenses();
            $Expense->setName($E_name);
            $Expense->setAmount($E_amount);
            $Expense->setType($E_type);

            $this->entityManager->persist($Expense);
            $this->entityManager->flush();

            $ExpensesList = $this->entityManager->getRepository(MExpenses::class)->findAll();
            $ExpensesList = $this->serializer->normalize($ExpensesList, 'json');

            return new JsonResponse([
                'status' => true,
                'message' => 'Ok',
                'data' => [$ExpensesList],
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
