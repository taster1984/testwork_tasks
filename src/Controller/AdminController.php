<?php


namespace App\Controller;


use App\My\Page;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/** Require ROLE_ADMIN for *every* controller method in this class.
 *
 * @IsGranted("ROLE_ADMIN")
 */
class AdminController extends AbstractController
{
    /**
     * @Route("/admin")
     */
    public function admin(): Response
    {
        return $this->redirectToRoute('adminPage', ['page' => 1]);
    }

    /**
     * @Route("/admin/list/{page}", name="adminPage")
     */
    public function adminPage(Request $request, $page): Response
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
            $uri = $this->get('router')->generate('indexPage', ['page' => $i, 'order' => $order]);
            $ppage->number = $i;
            $ppage->uri = $uri;
            $pages[] = $ppage;
        }

        return $this->render("admin.html.twig", [
            'tasks' => $tasks,
            'toOrder' => $toOrder,
            'currentPage' => $page,
            'countPages' => $countPages,
            'pages' => $pages,
        ]);
    }

    /**
     * @Route("/admin/edit/{id}", name="editPage")
     */
    public function editPage(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $task = $em->find("App\Entity\Task", $id);

        $form = $this->createFormBuilder($task)
            ->add('description', TextType::class, ['label' => 'Описание задачи '])
            ->add('email', EmailType::class, ['label' => 'Email автора '])
            ->add('save', SubmitType::class, array('label' => 'Сохранить'))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $task = $form->getData();
            $task->setEdited(true);

            $em = $this->getDoctrine()->getManager();
            $em->persist($task);
            $em->flush();

            return $this->redirectToRoute('adminPage', ['page' => 1]);
        }

        return $this->render("editTask.html.twig", ['form' => $form->createView()]);


    }

    /**
     * @Route("/admin/close/{id}", name="close")
     */
    public function close($id)
    {
        $em = $this->getDoctrine()->getManager();
        $task = $em->find("App\Entity\Task", $id);
        $task->setStatus(1);
        $em->persist($task);
        $em->flush();
        return $this->redirectToRoute('adminPage', ['page' => 1]);
    }
}