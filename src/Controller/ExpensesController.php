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
     * @Route("/expenses/getall/{user_email}", name="expenses_getAll")
     */
    public function getAll($user_email): JsonResponse
    {
        try {
            $User = $this->entityManager->getRepository(Users::class)->findOneBy(['email' => $user_email]);
            $ExpensesList = $this->entityManager->getRepository(MExpenses::class)->findBy(['user_id' => $User->getId()]);
            $ExpensesList = $this->serializer->normalize($ExpensesList, 'json');

            # Calculamos el monto total de gastos
            $total = 0;
            $pending_to_pay = 0;
            foreach($ExpensesList as $Expense){
                if(!$Expense['checked']){
                    $pending_to_pay += $Expense['amount'];
                }else{
                    $total += $Expense['amount'];
                }
            }

            return new JsonResponse([
                'status' => true,
                'message' => 'Ok',
                'data' => [
                    'expenses' => $ExpensesList,
                    'acount' => [
                        'salary' => $User->getSalary(),
                        'total' => $total,
                        'status' => ($User->getSalary() - $total),
                        'pending_to_pay' => $pending_to_pay
                    ]
                ]
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
            $U_id = $params['user_id'];

            if (!$E_name || !$E_amount || !$U_id) {
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
            $Expense->setUserId($U_id);

            $this->entityManager->persist($Expense);
            $this->entityManager->flush();

            $ExpensesList = $this->entityManager->getRepository(MExpenses::class)->findBy(['user_id' => $U_id]);
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
     * @Route("/expenses/checked/{id}", name="expenses_checked", methods={"PUT"})
     */
    public function checkedExpense($id): JsonResponse
    {
        try {
            $Expense = $this->entityManager->getRepository(MExpenses::class)->findOneBy(['id' => $id]);

            $Expense->setChecked(!$Expense->isChecked());

            $this->entityManager->persist($Expense);
            $this->entityManager->flush();

            $ExpensesData = $this->serializer->normalize($Expense, 'json');

            return new JsonResponse([
                'status' => true,
                'message' => 'Ok',
                'data' => $ExpensesData,
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
