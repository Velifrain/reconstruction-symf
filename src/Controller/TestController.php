<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\Feet;
use App\Form\FeetType;
use League\Flysystem\Exception;
use League\Flysystem\FileExistsException;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
     * @Route("/test", name="test")
     */
    public function index(Request $request, FilesystemInterface $feetStorage): Response
    {
        $feet = new Feet();
        $feetForm = $this->createForm(FeetType::class, $feet);
        $feetForm->handleRequest($request);

        if ($request->isMethod('POST') && $feetForm->isSubmitted() && $feetForm->isValid()) {
            $em = $this->getDoctrine()->getManager();

            /** @var UploadedFile $feetCover */
            $feetCover = $feetForm->get('cover')->getData();

            if ($feetCover->isValid()) {
                $fileCover = md5(uniqid()) . '.' . $feetCover->guessExtension();
                $stream = fopen($feetCover->getRealPath(), 'r+');
                $feetStorage->writeStream(DIRECTORY_SEPARATOR . $fileCover, $stream);
                fclose($stream);

                $feet->setCover(self::PATH_TO_UPLOADED_FILE . $fileCover);
            }

            $galleryFeet = [];

            $feetGallery = $feetForm->get('gallery')->getData();
            /** @var UploadedFile $gallery */
            foreach ($feetGallery as $gallery) {
                if ($gallery->isValid()) {
                    $stream = fopen($gallery->getRealPath(), 'r+');
                    $filesGallery = md5(uniqid()) . '.' . $gallery->guessExtension();
                    // md5() уменьшает схожесть имён файлов, сгенерированных
                    // uniqid(), которые основанный на временных отметках - генерация уникального имени файла
                    $feetStorage->writeStream(DIRECTORY_SEPARATOR . $filesGallery, $stream);
                    fclose($stream);
                }
                $galleryFeet[] = $gallery->getRealPath();
            }
            $feet->setGallery($galleryFeet);
            $em->persist($feet);
            $em->flush();


            return $this->redirectToRoute('test');
        }

        if (!$feetForm->get('cover')->getData()) {
            $feetForm->get('cover')->addError(new FormError('Поле не должно быть пустым'));
        }

        return $this->render('test/index.html.twig', [
            'feetForm' => $feetForm->createView(),
        ]);
    }

    /**
     * @Route("/update/{id}", name="feet_update", methods={"GET","POST"})
     * @param Request $request
     * @param FilesystemInterface $feetStorage
     * @param Feet $feet
     * @return Response
     * @throws FileExistsException
     * @throws Exception
     */
    public function update(Request $request, Feet $feet, FilesystemInterface $feetStorage): Response
    {
        $feetFormUpdate = $this->createForm(FeetType::class, $feet);
        $feetFormUpdate->handleRequest($request);

        $existingCover = $this->getParameter('upload_files') . DIRECTORY_SEPARATOR . 'public' . $feet->getCover();

        if ($feetFormUpdate->isSubmitted() && $feetFormUpdate->isValid()) {
            /** @var UploadedFile $imageCover */
            $imageCover = $feetFormUpdate->get('cover')->getData();

            if ($imageCover && $imageCover->isValid()) {

                $imageCoverName = md5(uniqid()) . '.' . $imageCover->guessExtension();
                $stream = fopen($imageCover->getRealPath(), 'r+');
                $feetStorage->writeStream(DIRECTORY_SEPARATOR . $imageCoverName, $stream);
                fclose($stream);

                if (file_exists($existingCover)) {
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
