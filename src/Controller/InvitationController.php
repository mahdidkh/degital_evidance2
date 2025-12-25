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
use Symfony\Component\Security\Http\Authenticator\FormLoginAuthenticator; // Assuming default authenticator, might need adjustment based on config
// Actually, for now auto-login might be complex without knowing exact authenticator service name. 
// I will just redirect to login page after setup.

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

            // check if user exists
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
            // generate random token
            $token = bin2hex(random_bytes(32));
            $user->setInvitationToken($token);
            $user->setPassword('pending_setup'); 
            $user->setPassword(bin2hex(random_bytes(16))); // Random temp password
            $user->setIsActive(true);
            
            // Dummy required fields
            $user->setCin('PENDING_' . uniqid()); 
            $user->setFirstName('Invited');
            $user->setLastName('User');
            $user->setTel('0000000000');
            // Investigateur specific
            if ($user instanceof Investigateur) {
                $user->setEmployerId('PENDING');
                $user->setExpertArea('PENDING');
            }
            // Supervisor specific
            if ($user instanceof Supervisor) {
                $user->setTeamScoop('PENDING');
                $user->setEscalation('PENDING');
            }


            $entityManager->persist($user);
            $entityManager->flush();

            // Generate Link
            $link = $this->generateUrl('app_invitation_setup', ['token' => $token], \Symfony\Component\Routing\Generator\UrlGeneratorInterface::ABSOLUTE_URL);
            
            // Send Email
            $emailMessage = (new \Symfony\Bridge\Twig\Mime\TemplatedEmail())
                ->from('admin@digitalevidence.com') // Change to your sender
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
                // Still show link as fallback
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
            
            // Encode password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );

            // Clear token
            $user->setInvitationToken(null);
            $user->setIsVerified(true);
            
            // Set fields that were pending if needed, or form already mapped them (First/Last/Tel/CIN)
            // Investigateur/Supervisor specific fields might still be PENDING if not in form.
            // For now we assume User fills generic info, Admin or User updates specific info later?
            // "the compt attribute it like an email link token to her account" - implies just getting access.
            
            $entityManager->flush();

            $this->addFlash('success', 'Account set up successfully! Please login.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/setup_account.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
