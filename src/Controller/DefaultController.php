<?php

namespace App\Controller;

use App\Entity\CalculateStatistics;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Interfaces\ScoreDataIndexerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Yectep\PhpSpreadsheetBundle\Factory;

class DefaultController extends AbstractController
{

    private $client;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->client = $httpClient;
    }

    /**
     * @Route("/",name="index")
     */
    public function index(Request $request){
        $calculated = false;
        $results = [];
        $form = $this->form();
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->get("security.csrf.token_manager")->refreshToken("form_intention");

            $rangeStart = $form->get('rangeStart')->getData();
            $rangeEnd = $form->get('rangeEnd')->getData();
            $region = $form->get('region')->getData();
            $gender = $form->get('gender')->getData();
            $legalAge = $form->get('legalAge')->getData();
            $score = $form->get('score')->getData();
            $csv = $form->get('csv')->getData();

            
            if($_FILES['form']['tmp_name']['csv'] == ""){
                $this->addFlash('error', 'Error, please upload a valid CSV');
            }


            $dataCSV = $this->processCSV($_FILES);
            if (!empty($dataCSV)) {
                $index = new CalculateStatistics;
                $index->setUsers($dataCSV);
                $results['scoreTotal'] = $index->getCountOfUsersWithinScoreRange($rangeStart, $rangeEnd);
                $results['countCondition'] = $index->getCountOfUsersByCondition($region, $gender, $legalAge, $score);
                $calculated = true;

                
                $results = $this->renderView('history/results.html.twig', [
                    'results' => $results
                ]);
                
            }else{
                $this->addFlash('error', 'Error, please upload a valid CSV');
            }

        }

         return $this->render("default/index.html.twig", [
            'form' => $form->createView(),
            'results' => $results,
            'calculated' => $calculated
        ]);
    }

    public function processCSV($csv)
    {
        $test = new Factory;
        $readerXlsx = $test->createReader('Csv');
        $spreadsheet = $readerXlsx->load($csv['form']['tmp_name']['csv']);
        $rows = $spreadsheet->getActiveSheet()->toArray();

        $headers = array_shift($rows);
        array_walk($rows, function(&$values) use($headers){
            $values = array_combine($headers, $values);
        });

        return $rows;
    }

    public function form(){

        $defaultData = ['message' => 'Please provide the following information'];
        $form = $this->createFormBuilder($defaultData)
            ->add('rangeStart', IntegerType::class, [
                'label' => 'Range Start',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Range Start'
                ],

            ])
            ->add('rangeEnd', IntegerType::class, [
                'label' => 'Range End',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Range End'
                ],

            ])
            ->add('region', TextType::class, [
                'label' => 'Region',
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Region'
                ],

            ])
            ->add('gender', ChoiceType::class, [
                'label' => 'Gender',
                'required' => "yes",
                'mapped' => false,
                'choices' => [
                    'Woman' => 'w',
                    'Man' => 'm'
                ],
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('legalAge', ChoiceType::class, [
                'label' => 'Has legal age?',
                'required' => true,
                'mapped' => false,
                'choices' => [
                    'Yes' => '1',
                    'No' => '0'
                ],
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('score', ChoiceType::class, [
                'label' => 'Has positive score?',
                'required' => true,
                'mapped' => false,
                'choices' => [
                    'Yes' => '1',
                    'No' => '0'
                ],
                'attr' => [
                    'class' => 'form-control'
                ]
            ])
            ->add('csv', FileType::class, [
                'label' => 'CSV File',
                'attr' => [
                    'class' => 'form-control',
                    'accept' => '.csv'
                ],
                'data_class' => null,
                'required' => true,
                'empty_data' => ''
            ])
            ->add('send', SubmitType::class)
            ->getForm();
            
        return $form;
    }

    /**
     * @Route("/converter",name="converter")
     */
    public function converter(Request $request){


        if ($form->isSubmitted()) {
            // encode the plain password
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('password')->getData()
                )
            );
            $user->clearResetToken();
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('security_login_landing');
        }



        $html = $this->renderView('history/results.html.twig', [
            'results' => $results
        ]);

        return new JsonResponse($html);


    }


    /**
     * @Route("/history",name="history")
     */
    public function history(Request $request, HistoryInfoRepository $HistoryInfoRepo){

        $histories = $HistoryInfoRepo->findBy(
            array(),
            array('createdAt' => 'DESC')
        );

        return $this->render("history/index.html.twig", [
            'histories' => $histories
        ]);
    }

    /**
     * @Route("/order",name="order")
     */
    public function order(Request $request, HistoryInfoRepository $HistoryInfoRepo){
        $type = $request->get('type');

        $histories = $HistoryInfoRepo->findBy(
            array(),
            array('createdAt' => $type)
        );

        $html = $this->renderView('history/order.html.twig', [
            'histories' => $histories
        ]);

        return new JsonResponse($html);


    }

}