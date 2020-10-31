<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\Feet;
use App\Form\FeetType;
use League\Flysystem\Exception;
use League\Flysystem\FileExistsException;
use League\Flysystem\FileNotFoundException;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;


/**
 * Class TestController
 * @package App\Controller
 */
class TestController extends AbstractController
{

    const PATH_TO_UPLOADED_FILE = '/inventory/feet/';

    /**
     * @param Feet $feet
     * @param FilesystemInterface $feetStorage
     * @param Request $request
     * @Route("/test/update/{id}/file/", name="feet_ajax_upload_files", methods={"POST"})
     * @param ValidatorInterface $validator
     * @return JsonResponse
     * @throws FileExistsException
     */
    public function ajaxUploadImage(
        Request $request, Feet $feet,
        FilesystemInterface $feetStorage,
        ValidatorInterface $validator): JsonResponse
    {
        if (!$request->isXmlHttpRequest()) {
            throw new BadRequestHttpException('Only ajax');
        }
        // получить загруженый файл галереи если есть
        $files = $request->files->get('feet')['gallery'] ?? [];

        $constraint = new All([
            new NotBlank(),
            new Image([
                'mimeTypes' => [
                    "image/png",
                    "image/jpg",
                    "image/jpeg",
                    "image/gif",
                ],
                'mimeTypesMessage' => 'Please upload a valid Files',
            ])
        ]);

        $errors = $validator->validate(
            $files,
            $constraint
        //[] валідація на коллекці файлів
        );

        if (0 === count($errors)) {
            // збереження файлів
            $filesFeet = $feet->getGallery();
            foreach ($files as $file) {
                $fileName = md5(uniqid()) . '.' . $file->getClientOriginalExtension();
                $stream = fopen($file->getRealPath(), 'r+');
                $feetStorage->writeStream($fileName, $stream);
                fclose($stream);

                $filesFeet[] = self::PATH_TO_UPLOADED_FILE . $fileName;
            }
            $feet->setGallery($filesFeet);
            $this->getDoctrine()->getManager()->flush();

            // повернення списку файлів
            return new JsonResponse([
                'filesFeet' => [
                    'image' => $feet->getId(),
                ]
            ]);
        } else {
            // обробка помилки

            // повернення помилки??? як правильно зробити?
            $errorsString = (string)$errors;
            return new JsonResponse($errorsString, 400);
        }
    }

    /**
     * @param Request $request
     * @param Feet $feet
     * @param FilesystemInterface $feetStorage
     * @return JsonResponse
     * @throws FileNotFoundException
     * @Route("/test/delete/{id}", name="feet_delete", methods={"POST"})
     */
    public function deleteAjaxFiles(Request $request, Feet $feet, FilesystemInterface $feetStorage)
    {
        $em = $this->getDoctrine()->getManager();
        if ($request->isXmlHttpRequest()) {
            $result = [];
            $valueFile = '/inventory/feet/'. $request->request->get('file_name');
            foreach ($feet->getGallery() as $item => $value) {
                if($valueFile == $value){
                    unset($item[$valueFile]);
                } else {
                    $result[] = $value;
                }

            }
            $feet->setGallery($result);
            $em->flush();

        }

        return new JsonResponse('');
    }

    /**
     * @param Request $request
     * @param FilesystemInterface $feetStorage
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws FileExistsException
     * @Route("/test/create", name="feet_create")
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
                    $fileGallery = md5(uniqid()) . '.' . $gallery->getClientOriginalExtension();
                    $stream = fopen($gallery->getRealPath(), 'r+');
                    $feetStorage->writeStream($fileGallery, $stream);
                    fclose($stream);

                    $galleryFeet[] = self::PATH_TO_UPLOADED_FILE . $fileGallery;
                    $feet->setGallery($galleryFeet);
                }
            }
            $em->persist($feet);
            $em->flush();
            $this->addFlash(
                'notice',
                'Your changes were saved!'
            );

            return $this->redirectToRoute('feet_create');
        }

        return $this->render('test/create.html.twig', [
            'feetForm' => $feetForm->createView(),
        ]);
    }

    /**
     * @Route("/test/update/{id}", name="feet_update", methods={"POST", "GET"})
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

        $existingCover = substr($feet->getCover(), 16);

        if ($feetFormUpdate->isSubmitted() && $feetFormUpdate->isValid()) {
            /** @var UploadedFile $imageCover */
            $imageCover = $feetFormUpdate->get('cover')->getData();

            if ($imageCover && $imageCover->isValid()) {
                if ($feetStorage->has($existingCover)) {
                    $feetStorage->delete($existingCover);
                }

                $imageCoverName = md5(uniqid()) . '.' . $imageCover->getClientOriginalExtension();
                $stream = fopen($imageCover->getRealPath(), 'r+');
                $feetStorage->writeStream($imageCoverName, $stream);
                fclose($stream);

                $feet->setCover(self::PATH_TO_UPLOADED_FILE . $imageCoverName);
            } else {
                $feet->setCover($feet->getCover());
            }

            $galleryImageOldNew = $feet->getGallery();

            if ($request->isXmlHttpRequest()) {
                $imageGallery = $feetFormUpdate->get('gallery')->getData();
                /** @var UploadedFile $gallery */
                foreach ($imageGallery as $gallery) {
                    if ($gallery->isValid()) {

                        $imagesGalleryName = md5(uniqid()) . '.' . $gallery->getClientOriginalExtension();
                        $stream = fopen($gallery->getRealPath(), 'r+');
                        $feetStorage->putStream($imagesGalleryName, $stream);
                        fclose($stream);

                        $galleryImageOldNew[] = self::PATH_TO_UPLOADED_FILE . $imagesGalleryName;
                        $feet->setGallery($galleryImageOldNew);
                    }
                }
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
