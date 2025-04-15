<?php

namespace App\Controller\hebergements;
use App\Helper\ChambreImageUploader;
use App\Entity\Image;
use App\Form\ImageType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/image')]
final class ImageController extends AbstractController
{
    #[Route('/new', name: 'app_image_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        ChambreImageUploader $uploader
    ): Response {
        $image = new Image();
        $form = $this->createForm(ImageType::class, $image);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $file */
            $file = $form->get('imageFile')->getData();
            $imagePath = $uploader->upload($file);
            $image->setUrlImage($imagePath);

            $entityManager->persist($image);
            $entityManager->flush();

            return $this->redirectToRoute('app_image_index');
        }

        return $this->render('hebergements/image/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_image_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        Image $image, 
        EntityManagerInterface $entityManager,
        ChambreImageUploader $uploader
    ): Response {
        $oldImage = $image->getUrlImage();
        $form = $this->createForm(ImageType::class, $image);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($file = $form->get('imageFile')->getData()) {
                $uploader->delete($oldImage);
                $imagePath = $uploader->upload($file);
                $image->setUrlImage($imagePath);
            }

            $entityManager->flush();
            return $this->redirectToRoute('app_image_index');
        }

        return $this->render('hebergements/image/edit.html.twig', [
            'image' => $image,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_image_delete', methods: ['POST'])]
    public function delete(
        Request $request, 
        Image $image, 
        EntityManagerInterface $entityManager,
        ChambreImageUploader $uploader
    ): Response {
        if ($this->isCsrfTokenValid('delete'.$image->getId(), $request->request->get('_token'))) {
            $uploader->delete($image->getUrlImage());
            $entityManager->remove($image);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_image_index');
    }
}