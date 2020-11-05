<?php


namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Task;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\My\Page;

class MainController extends AbstractController
{
    /**
     * @Route("/list/{page}", name="indexPage")
     */
    public function indexPage(Request $request, $page = 1): Response
    {
        $c = 5;
        if ($page < 1) $page = 1;
        $order = $request->get("order");
        $em = $this->getDoctrine()->getManager();
        $qb = $em->createQueryBuilder();
        if ($order == null || (strtoupper($order) != 'ASC' && strtoupper($order) != 'DESC')) {
            $tasks = $qb->select('t')
                ->from('App\Entity\Task', 't')
                ->orderBy('t.id', 'DESC')
                ->setFirstResult(($page - 1) * $c)
                ->setMaxResults($c)
                ->getQuery()
                ->getResult();
            $toOrder = "desc";
        } else {
            $tasks = $qb->select('t')
                ->from('App\Entity\Task', 't')
                ->orderBy('t.status', $order)
                ->setFirstResult(($page - 1) * $c)
                ->setMaxResults($c)
                ->getQuery()
                ->getResult();
            if (strtoupper($order) == 'DESC')
                $toOrder = 'asc';
            else
                $toOrder = 'desc';
        }

        $qb = $em->createQueryBuilder();
        $qb->select('count(task.id)');
        $qb->from('App\Entity\Task', 'task');
        $count = $qb->getQuery()->getSingleScalarResult();
        $countPages = (int)ceil($count / $c);
        $pages = [];
        for ($i = 1; $i <= $countPages; $i++) {
            $ppage = new Page();
            $uri = $this->get('router')->generate('indexPage', ['page' => $i,'order'=>$order]);
            $ppage->number = $i;
            $ppage->uri = $uri;
            $pages[] = $ppage;
        }

        return $this->render("index.html.twig", [
            'tasks' => $tasks,
            'toOrder' => $toOrder,
            'currentPage' => $page,
            'countPages' => $countPages,
            'pages' => $pages,
        ]);
    }

    /**
     * @Route("/", name="index")
     */
    public function index()
    {
        return $this->redirectToRoute('indexPage', ['page' => 1]);
    }

    /**
     * @Route("/addTask", name="addTask")
     */
    public function addTask(Request $request): Response
    {
        $task = new Task();

        $form = $this->createFormBuilder($task)
            ->add('description', TextType::class, ['label' => 'Описание задачи '])
            ->add('email', EmailType::class, ['label' => 'Email автора '])
            ->add('save', SubmitType::class, array('label' => 'Добавить задачу'))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $task = $form->getData();
            $task->setStatus(0);
            $task->setEdited(false);

            $em = $this->getDoctrine()->getManager();
            $em->persist($task);
            $em->flush();

            return $this->redirectToRoute('indexPage', ['page' => 1]);
        }

        return $this->render("addTask.html.twig", ['form' => $form->createView()]);
    }
}