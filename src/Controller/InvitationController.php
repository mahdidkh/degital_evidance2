<?php

namespace App\Controller;

use App\Entity\Investigateur;
use App\Entity\Supervisor;
use App\Entity\User;
use App\Form\AccountSetupType;
use App\Form\UserInvitationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator; 
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMINISTRATEUR')]
class InvitationController extends AbstractController
{
    #[Route('/admin/invite', name: 'admin_invite')]
    public function invite(Request $request, EntityManagerInterface $entityManager, \Symfony\Component\Mailer\MailerInterface $mailer): Response
    {
        $form = $this->createForm(UserInvitationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $email = $data['email'];
            $role = $data['role'];

          
            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingUser) {
                $this->addFlash('error', 'User with this email already exists.');
                return $this->redirectToRoute('admin_invite');
            }

            $user = null;
            if ($role === 'investigateur') {
                $user = new Investigateur();
                $user->setRole('ROLE_INVESTIGATEUR');
            } else {
                $user = new Supervisor();
                $user->setRole('ROLE_SUPERVISOR');
            }

            $user->setEmail($email);
            
            $token = bin2hex(random_bytes(32));
            $user->setInvitationToken($token);
            $user->setPassword('pending_setup'); 
            $user->setPassword(bin2hex(random_bytes(16))); 
            $user->setIsActive(true);
            
           
            $user->setCin('PENDING_' . uniqid()); 
            $user->setFirstName('Invited');
            $user->setLastName('User');
            $user->setTel('00000000');
           
            if ($user instanceof Investigateur) {
                $user->setEmployerId('PENDING');
                $user->setExpertArea('PENDING');
            }
         
            if ($user instanceof Supervisor) {
                $user->setTeamScoop('PENDING');
                $user->setEscalation('PENDING');
            }


            $entityManager->persist($user);
            $entityManager->flush();

            
            $link = $this->generateUrl('app_invitation_setup', ['token' => $token], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);
            
            
            $emailMessage = (new \Symfony\Bridge\Twig\Mime\TemplatedEmail())
                ->from('admin@digitalevidence.com') 
                ->to($user->getEmail())
                ->subject('Invitation to Digital Evidence Platform')
                ->htmlTemplate('emails/invitation.html.twig')
                ->context([
                    'link' => $link,
                    'role' => ucfirst($role),
                ]);

            try {
                $mailer->send($emailMessage);
                $this->addFlash('success', 'Invitation sent successfully to ' . $email);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Invitation created but failed to send email: ' . $e->getMessage());
               
                $this->addFlash('info', 'Manual Link: ' . $link);
            }
            
            return $this->redirectToRoute('admin_invite');
        }

        return $this->render('admin/invite.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/invitation/{token}', name: 'app_invitation_setup')]
    public function setup(
        string $token, 
        Request $request, 
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $userPasswordHasher
    ): Response
    {
        $user = $entityManager->getRepository(User::class)->findOneBy(['invitationToken' => $token]);

        if (!$user) {
            throw $this->createNotFoundException('Invalid invitation token.');
        }

        $form = $this->createForm(AccountSetupType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
           
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

           
            $user->setInvitationToken(null);
            $user->setIsVerified(true);
            
            
            
            $entityManager->flush();

            $this->addFlash('success', 'Account set up successfully! Please login.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/setup_account.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
