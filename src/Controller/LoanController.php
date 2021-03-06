<?php

namespace App\Controller;

use App\Entity\Loan;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Class LoanController
 *
 * @package App\Controller
 *
 * @Route("/loans")
 */
class LoanController extends AbstractController
{
    /**
     * @Route("", name="app_loan_index", methods={"GET"})
     *
     * @return Response
     */
    public function index() : Response
    {
        $loanRepository = $this->getDoctrine()->getRepository(Loan::class);
        return $this->render('App/Loan/index.html.twig', [
            'loans' => $loanRepository->findLent(),
            'returnedItems' => $loanRepository->findReturned(),
        ]);
    }

    /**
     * @Route("/{id}/delete", name="app_loan_delete", requirements={"id"="%uuid_regex%"}, methods={"GET"})
     *
     * @param Loan $loan
     * @param TranslatorInterface $translator
     * @return Response
     */
    public function delete(Loan $loan, TranslatorInterface $translator) : Response
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($loan);
        $em->flush();

        $this->addFlash('notice', $translator->trans('message.loan_canceled', ['%item%' => '&nbsp;<strong>'.$loan->getItem()->getName().'</strong>&nbsp;']));

        return $this->redirect($this->generateUrl('app_loan_index'));
    }

    /**
     * @Route("/{id}/returned", name="app_loan_returned", requirements={"id"="%uuid_regex%"}, methods={"GET"})
     * @Entity("loan", expr="repository.findByIdWithItem(id)")
     *
     * @param Loan $loan
     * @param TranslatorInterface $translator
     * @return Response
     */
    public function returned(Loan $loan, TranslatorInterface $translator) : Response
    {
        $loan->setReturnedAt(new \Datetime());
        $this->getDoctrine()->getManager()->flush();
        $this->addFlash('notice', $translator->trans('message.item_returned', ['%item%' => '&nbsp;<strong>'.$loan->getItem()->getName().'</strong>&nbsp;']));

        return $this->redirect($this->generateUrl('app_loan_index'));
    }
}
