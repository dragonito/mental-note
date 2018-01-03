<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

class UserController extends AbstractBaseController
{

    /**
     * @Route("/user/change-password",name="user_change_password")
     * @Template()
     * @Method({"GET", "POST"})
     */
    public function indexAction(Request $request)
    {
        $user = $this->getUser();

        $form = $this->container->get('fos_user.change_password.form.factory')->createForm();
        $form->setData($user);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $userManager = $this->get('fos_user.user_manager');
            $userManager->updateUser($user);

            $url = $this->container->get('router')->generate('homepage');

            return new RedirectResponse($url);
        }

        return array(
            'user' => $user,
            'form' => $form->createView(),
        );
    }
}