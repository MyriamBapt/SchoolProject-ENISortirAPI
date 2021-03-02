<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Repository\CampusRepository;
use App\Service\GenerateToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ParticipantController extends AbstractController
{
    /**
     * @Route("/api/register/participant", name="addParticipant", methods={"POST"})
     */
    public function addParticipant(Request $request, EntityManagerInterface $em, ValidatorInterface $validator,
                                   SerializerInterface $serializer, UserPasswordEncoderInterface $passwordEncoder,
                                   GenerateToken $generateToken, CampusRepository $campusRepository): Response
    {
        $jsonRecu= $request->getContent();
        $participant= $serializer->deserialize($jsonRecu, Participant::class, 'json');
        $id_campus=json_decode($jsonRecu)->campus->id;
        $campus= $campusRepository->find($id_campus);
        $participant->setCampus($campus);
        $participant= $generateToken->getToken($participant);
        $error= $validator->validate($participant);
        if(count($error)>0){
            return $this->json($error,400);
        }else{
            $participant->setPassword($passwordEncoder->encodePassword($participant, $participant->getPassword()));
            if($participant->getAdministrateur()){
                $participant->addRoles('ROLE_ADMIN');
            }
            $em->persist($participant);
            $em->flush();
            return $this->json($participant, 201, [], ['groups'=>'participant:read']);
        }
    }
}
