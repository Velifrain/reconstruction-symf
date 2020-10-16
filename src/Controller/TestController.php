<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\Feet;
use App\Form\FeetType;
use League\Flysystem\Exception;
use League\Flysystem\FileExistsException;
use League\Flysystem\FilesystemInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
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
use Symfony\Component\Validator\ConstraintViolation;


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
     * @Route("/test/update/file/{id}", name="feet_ajax_upload_files", methods={"GET"})
     * @throws FileExistsException
     * @return JsonResponse
     */
    public function ajaxUploadImage(Request $request, Feet $feet, FilesystemInterface $feetStorage): JsonResponse
    {
        $em = $this->getDoctrine()->getManager();

        $form = $this->createFormBuilder($feet)
            ->add('gallery', FileType::class, [
                'data_class' => Feet::class,
                'mapped' => false,
                'multiple' => true,
                'constraints' => [
                    new All([
                        new NotBlank(),
                        new Image([
                            'mimeTypes' => [
                                "image/png",
                                "image/jpeg",
                                "image/jpg",
                                "image/gif",
                            ],
                            'mimeTypesMessage' => 'Please upload a valid File',
                        ]),
                    ])
                ],
            ])
            ->getForm();


        $form->handleRequest($request);

        // check POST and AJAX only$ (if not - throw Exception)
        if ($request->isMethod('GET')) {

            if ($request->isXmlHttpRequest()) {
                // check constraints (if not - return error message(s))
                if ($form->isSubmitted() && $form->isValid()) {
                    // save file (if not - return error message(s))
                    /** @var UploadedFile $imageCover */
                    $image = $form->get('gallery')->getData();

                    if ($image && $image->isValid()) {

                        $imageCoverName = md5(uniqid()) . '.' . $imageCover->getClientOriginalExtension();
                        $stream = fopen($imageCover->getRealPath(), 'r+');
                        $feetStorage->writeStream($imageCoverName, $stream);
                        fclose($stream);

                        $feet->setCover(self::PATH_TO_UPLOADED_FILE . $imageCoverName);
                    }

                    // save feet (if not - delete file and return error message(s))
                    $em->persist($feet);
                    $em->flush();

                } else {
                    // TODO get error
                }

            } else {
                //throw new BadRequestHttpException('Not Ajax');
            }
        } else {
            throw new BadRequestHttpException('Not POST');
        }

            return new JsonResponse([
                'file_list' => [
                    'data' => $feet->getGallery(),
                ]
            ]);

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
                $feetStorage->has($existingCover);
                $feetStorage->delete($existingCover);

                $imageCoverName = md5(uniqid()) . '.' . $imageCover->getClientOriginalExtension();
                $stream = fopen($imageCover->getRealPath(), 'r+');
                $feetStorage->writeStream($imageCoverName, $stream);
                fclose($stream);

                $feet->setCover(self::PATH_TO_UPLOADED_FILE . $imageCoverName);
            } else {
                $feet->setCover($feet->getCover());
            }

            $imageGallery = $feetFormUpdate->get('gallery')->getData();
            $galleryFeet = [];

            /** @var UploadedFile $gallery */
            foreach ($imageGallery as $gallery) {
                if ($gallery->isValid()) {
//                    if (file_exists($existingCover)) {
//                        $feetStorage->delete(substr($feet->getCover(), 16));
//                    }
                    $imagesGalleryName = md5(uniqid()) . '.' . $gallery->getClientOriginalExtension();
                    $stream = fopen($gallery->getRealPath(), 'r+');
                    $feetStorage->putStream($imagesGalleryName, $stream);
                    fclose($stream);

                    $galleryFeet[] = self::PATH_TO_UPLOADED_FILE . $imagesGalleryName;
                    $feet->setGallery($galleryFeet);
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
