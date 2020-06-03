<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;



class AuthController extends AbstractController
{
    /**
     * @Route("/register", name="user_registration")
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder, ClientRegistry $clientRegistry, \Swift_Mailer $mailer )
    {

        if ($this->isGranted('ROLE_USER')){
            return $this->redirect('/');
        }
        $defaults = [];

        $user = new User();
        if($request->get('code') && empty($request->request->all())){
            $userDatas = $clientRegistry->getClient('facebook')->fetchUser()->toArray();
            $defaults = [
                'email' =>$userDatas['email'],
                'username' =>$userDatas['first_name'] . ucfirst($userDatas['last_name'])
            ];
            $request->query->remove('code');
        }
        // 1) build the form
        $form = $this->createFormBuilder($defaults)
            ->add('email', EmailType::class, array('label' => false, 'required' => false))
            ->add('username', TextType::class, array('label' => false, 'required' => false))
            ->add('birth', TextType::class, array('label' => false, 'required' => false))
            ->add('plainPassword', RepeatedType::class, array(
                'required' => false,
                'type' => PasswordType::class,
                'first_options'  => array('label' => false, 'required' => false),
                'second_options' => array('label' => false, 'required' => false)
            ))
            ->getForm();

        // 2) handle the submit (will only happen on POST
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            dd('ok');
            // 3) Encode the password (you could also do this via Doctrine listener)
            $password = $passwordEncoder->encodePassword($user, $form->get('plainPassword')->getData());
            $user->setPassword($password);
            $user->setEmail($form->get('email')->getData());
            $user->setBirth(new \DateTime($form->get('birth')->getData()));
            $user->setUsername($form->get('username')->getData());
            //check if email is not already in db
            $repository = $this->getDoctrine()->getRepository(User::class);
            $userExist = $repository->findOneBy(['email' => $user->getEmail()]);
            if($userExist){
                $this->addFlash('error', 'this Email seems already taken');
                return $this->redirectToRoute('user_registration');
            }
            // 4) save the User!
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();
            // 5) send mail for activation
            if($user->getId()){
                $mailLogger = new \Swift_Plugins_Loggers_ArrayLogger();
                $mailer->registerPlugin(new \Swift_Plugins_LoggerPlugin($mailLogger));
                $message = (new \Swift_Message('registration confirmation'))
                    ->setFrom('devshop@zakari-yassine.fr')
                    ->setTo('yassine.zakari@hotmail.fr')
                    ->setBody(
                        $this->renderView(
                        // templates/emails/registration.html.twig
                            'emails/registration.html.twig',
                            ['user' => $user ]
                        ),
                        'text/html'
                    )
                ;
                $mailer->send($message);
                $this->addFlash('notice', 'your account are created, please check your email for activation !');
                return $this->redirectToRoute('app_login');
            }

        }

        return $this->render(
            'register.html.twig',
            array('form' => $form->createView())
        );
    }

    /**
     * @Route("/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->isGranted('ROLE_USER')){
            return $this->redirect('/');
        }
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();
        return $this->render('login.html.twig',[
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }

    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout()
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * @Route("/confirmation/{mail}/{token}", name="user_confirmation")
     */
    public function confirmation(Request $request)
    {
        $mail = $request->attributes->get('mail');
        $token = $request->attributes->get('token');
        $repository = $this->getDoctrine()->getRepository(User::class);
        $user = $repository->findOneBy(['tokenRegistration' => $token]);
        if($mail === $user->getEmail()){
            $user->setStatus(true);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('notice', 'your mail are confirmed !');
            return $this->redirectToRoute('app_login');
        }
    }

    /**
     * @Route("/reset", name="user_password_reset")
     */
    public function reset(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        $mail = $request->get('mail');
        $token = $request->get('token');
        $repository = $this->getDoctrine()->getRepository(User::class);
        $user = $repository->findOneBy(['tokenRegistration' => $token]);
        if($user){
            if($mail === $user->getEmail()){
                $form = $this->createFormBuilder()
                    ->add('plainPassword', RepeatedType::class, array(
                        'required' => false,
                        'type' => PasswordType::class,
                        'first_options'  => array('label' => false, 'required' => false),
                        'second_options' => array('label' => false, 'required' => false)
                    ))
                    ->getForm();

                // 2) handle the submit (will only happen on POST
                $form->handleRequest($request);
                if ($form->isSubmitted() && $form->isValid()) {
                    $password = $passwordEncoder->encodePassword($user, $form->get('plainPassword')->getData());
                    $user->setPassword($password);
                    $user->setTokenRegistration();
                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->persist($user);
                    $entityManager->flush();
                    $this->addFlash('notice', 'Your password are successfully changed !');
                    return $this->redirectToRoute('app_login');
                }
                return $this->render(
                    'reset.html.twig',
                    array('form' => $form->createView())
                );
            }
        }

        return $this->redirectToRoute('app_login');
    }

    /**
     * @Route("/", name="home")
     * @IsGranted("ROLE_USER")
     */
    public function home()
    {
        if (!$this->isGranted('ROLE_USER')){
            return $this->redirect('/login');
        }
        return $this->render('home.html.twig');
    }

    /**
     * @Route("/generate", name="generate_password")
     */
    public function generatePassword(Request $request, \Swift_Mailer $mailer)
    {
        if ($this->isGranted('ROLE_USER')){
            return $this->redirect('/');
        }
        if ($request->isMethod('post')){
            $email = $request->request->get('email');
            $repository = $this->getDoctrine()->getRepository(User::class);
            $user = $repository->findOneBy(['email' => $email]);
            if(!$user){
                $this->addFlash('error', 'no account with this email');
                return $this->redirectToRoute('app_login');
            }
            $user->setTokenRegistration();
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();
            $message = (new \Swift_Message('registration confirmation'))
                ->setFrom('devshop@zakari-yassine.fr')
                ->setTo('yassine.zakari@hotmail.fr')
                ->setBody(
                    $this->renderView(
                    // templates/emails/registration.html.twig
                        'emails/generate.html.twig',
                        ['user' => $user ]
                    ),
                    'text/html'
                )
            ;
            $mailer->send($message);
            $this->addFlash('notice', 'an email will be send to you with a link to generate new password');
            return $this->redirectToRoute('app_login');

        }

        return $this->render('generate.html.twig');
    }

    /**
     * Link to this controller to start the "connect" process
     * @param ClientRegistry $clientRegistry
     *
     * @Route("/connect/facebook", name="connect_facebook_start")
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function facebookAction(ClientRegistry $clientRegistry)
    {

        return $clientRegistry
            ->getClient('facebook')
            ->redirect([
                'public_profile', 'email' // the scopes you want to access
            ])
            ;
    }
}