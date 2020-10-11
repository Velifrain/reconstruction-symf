<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\Feet;
use App\Form\FeetType;
use League\Flysystem\Exception;
use League\Flysystem\FileExistsException;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;


/**
 * Class TestController
 * @package App\Controller
 */
class TestController extends AbstractController
{

    const PATH_TO_UPLOADED_FILE = '/inventory/feet/';

    /**
     * @param Request $request
     * @param FilesystemInterface $feetStorage
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \League\Flysystem\FileExistsException
     * @Route("/test/create", name="test")
     */
    public function create(Request $request, FilesystemInterface $feetStorage): Response
    {
        $feet = new Feet();
        $feetForm = $this->createForm(FeetType::class, $feet);
        $feetForm->handleRequest($request);

        if ($request->isMethod('POST') && $feetForm->isSubmitted() && $feetForm->isValid()) {
            $em = $this->getDoctrine()->getManager();

            /** @var UploadedFile $feetCover */
            $feetCover = $feetForm->get('cover')->getData();

            if ($feetCover->isValid()) {
                $fileCover = md5(uniqid()) . '.' . $feetCover->getClientOriginalExtension();
                $stream = fopen($feetCover->getRealPath(), 'r+');
                $feetStorage->writeStream($fileCover, $stream);
                fclose($stream);

                $feet->setCover(self::PATH_TO_UPLOADED_FILE . $fileCover);
            }

            $galleryFeet = [];

            $feetGallery = $feetForm->get('gallery')->getData();
            /** @var UploadedFile $gallery */
            foreach ($feetGallery as $gallery) {
                if ($gallery->isValid()) {
                    $filesGallery = md5(uniqid()) . '.' . $gallery->getClientOriginalExtension();
                    $stream = fopen($gallery->getRealPath(), 'r+');
                    $feetStorage->writeStream($filesGallery, $stream);
                    fclose($stream);

                    $galleryFeet[] = self::PATH_TO_UPLOADED_FILE . $filesGallery;
                    $feet->setGallery($galleryFeet);
                }
            }

            $em->persist($feet);
            $em->flush();

            return $this->redirectToRoute('test');
        }

        return $this->render('test/create.html.twig', [
            'feetForm' => $feetForm->createView(),
        ]);
    }

    /**
     * @Route("/test/update/{id}", name="feet_update", methods={"GET","POST"})
     * @param Request $request
     * @param FilesystemInterface $feetStorage
     * @param Feet $feet
     * @return Response
     * @throws FileExistsException
     * @throws Exception
     */
    public function update(Request $request, Feet $feet, FilesystemInterface $feetStorage, LoggerInterface $logger): Response
    {
        $feetFormUpdate = $this->createForm(FeetType::class, $feet);
        $feetFormUpdate->handleRequest($request);

        $existingCover = $this->getParameter('upload_files') . DIRECTORY_SEPARATOR . 'public' . $feet->getCover();

        if ($feetFormUpdate->isSubmitted() && $feetFormUpdate->isValid()) {
            /** @var UploadedFile $imageCover */
            $imageCover = $feetFormUpdate->get('cover')->getData();

            if ($imageCover && $imageCover->isValid()) {
                $imageCoverName = md5(uniqid()) . '.' . $imageCover->getClientOriginalExtension();
                $stream = fopen($imageCover->getRealPath(), 'r+');
                $feetStorage->writeStream($imageCoverName, $stream);
                fclose($stream);

                if(file_exists($existingCover)){
                    unlink($existingCover);
                }

                $feet->setCover(self::PATH_TO_UPLOADED_FILE . $imageCoverName);
            } else {
                $feet->setCover($feet->getCover());
            }

            $this->getDoctrine()->getManager()->flush();
            $this->addFlash(
                'notice',
                'Your changes were saved!'
            );

            return $this->redirectToRoute('feet_update', ['id' => $feet->getId()]);
        }

        return $this->render('test/update.html.twig', [
            'feet' => $feet,
            'feetFormUpdate' => $feetFormUpdate->createView()
        ]);
    }
}
