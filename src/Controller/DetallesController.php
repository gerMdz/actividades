<?php

namespace App\Controller;

use App\Entity\Detalles;
use App\Form\DetallesType;
use App\Repository\DetallesRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/detalles")
 */
class DetallesController extends AbstractController
{
    /**
     * @Route("/", name="app_detalles_index", methods={"GET"})
     */
    public function index(DetallesRepository $detallesRepository): Response
    {
        return $this->render('detalles/index.html.twig', [
            'detalles' => $detallesRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="app_detalles_new", methods={"GET", "POST"})
     */
    public function new(Request $request, DetallesRepository $detallesRepository): Response
    {
        $detalle = new Detalles();
        $form = $this->createForm(DetallesType::class, $detalle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $detallesRepository->add($detalle, true);

            return $this->redirectToRoute('app_detalles_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('detalles/new.html.twig', [
            'detalle' => $detalle,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_detalles_show", methods={"GET"})
     */
    public function show(Detalles $detalle): Response
    {
        return $this->render('detalles/show.html.twig', [
            'detalle' => $detalle,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="app_detalles_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, Detalles $detalle, DetallesRepository $detallesRepository): Response
    {
        $form = $this->createForm(DetallesType::class, $detalle);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $detallesRepository->add($detalle, true);

            return $this->redirectToRoute('app_detalles_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('detalles/edit.html.twig', [
            'detalle' => $detalle,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="app_detalles_delete", methods={"POST"})
     */
    public function delete(Request $request, Detalles $detalle, DetallesRepository $detallesRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$detalle->getId(), $request->request->get('_token'))) {
            $detallesRepository->remove($detalle, true);
        }

        return $this->redirectToRoute('app_detalles_index', [], Response::HTTP_SEE_OTHER);
    }
}
