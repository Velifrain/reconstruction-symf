<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\Feet;
use App\Form\FeetType;
use League\Flysystem\FileExistsException;
use League\Flysystem\FilesystemInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;


/**
 *
 * Class UserController
 * @package App\Controller
 */
class UserController extends AbstractController
{
    const PATH_TO_UPLOADED_FILE = '/inventory/feet/';

    /**
     * @param AuthenticationUtils $utils
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @Route("/profile", name="profile_info")
     */
    public function index(AuthenticationUtils $utils): Response
    {
        $error = $utils->getLastAuthenticationError();

        $last_username = $utils->getLastUsername();

        return $this->render('user/index.html.twig',[
            'last_username' => $last_username,
            'error' => $error,
        ]);
    }

    /**
     * @param Request $request
     * @param FilesystemInterface $feetStorage
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws FileExistsException
     * @Route("/profile/create", name="user_create")
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

            return $this->redirectToRoute('user_create');
        }

        return $this->render('user/create.html.twig', [
            'feetForm' => $feetForm->createView(),
        ]);
    }
}
