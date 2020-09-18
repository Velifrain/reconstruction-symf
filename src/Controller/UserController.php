<?php
declare(strict_types=1);

namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;


/**
 *
 * Class UserController
 * @package App\Controller
 */
class UserController extends AbstractController
{
    /**
     * @param AuthenticationUtils $utils
     * @return /Response
     * @Route("/profile", name="profile_info")
     */
    public function index(AuthenticationUtils $utils)
    {
        $error = $utils->getLastAuthenticationError();

        $last_username = $utils->getLastUsername();

        return $this->render('user/index.html.twig',[
            'last_username' => $last_username,
            'error' => $error
        ]);
    }
}
