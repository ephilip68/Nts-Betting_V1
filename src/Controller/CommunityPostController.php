<?php

namespace App\Controller;

use App\Entity\CommunityPost;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CommunityPostController extends AbstractController
{
    #[Route('/community/post/create', name: 'app_community_post_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $entityManager
            ->getRepository(\App\Entity\User::class)
            ->find(1);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur de test introuvable.');
        }

        $post = new CommunityPost();

        $post->setUser($user);
        $type = $request->request->get('type');
        $content = $request->request->get('content');
        $image = $request->files->get('photo');

        if ($image) {
            $uploadDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/community';

            if (!is_dir($uploadDirectory)) {
                mkdir($uploadDirectory, 0775, true);
            }

            $sourcePath = $image->getPathname();

            $imageInfo = getimagesize($sourcePath);

            if ($imageInfo === false) {
                throw new \RuntimeException('Image invalide.');
            }

            [$width, $height] = $imageInfo;

            $maxWidth = 900;
            $maxHeight = 500;

            $ratio = min(
                $maxWidth / $width,
                $maxHeight / $height,
                1
            );

            $newWidth = (int) round($width * $ratio);
            $newHeight = (int) round($height * $ratio);

            switch ($imageInfo['mime']) {
                case 'image/jpeg':
                    $source = imagecreatefromjpeg($sourcePath);
                    break;

                case 'image/png':
                    $source = imagecreatefrompng($sourcePath);
                    break;

                case 'image/webp':
                    $source = imagecreatefromwebp($sourcePath);
                    break;

                default:
                    throw new \RuntimeException('Format d\'image non supporté.');
            }

            $resized = imagecreatetruecolor($newWidth, $newHeight);

            imagealphablending($resized, false);
            imagesavealpha($resized, true);

            imagecopyresampled(
                $resized,
                $source,
                0,
                0,
                0,
                0,
                $newWidth,
                $newHeight,
                $width,
                $height
            );

            $fileName = uniqid() . '.webp';
            $destination = $uploadDirectory . '/' . $fileName;

            imagewebp($resized, $destination, 82);

            imagedestroy($source);
            imagedestroy($resized);

            $post->setPhoto($fileName);
        } else {
            $post->setPhoto(null);
        }

        $post->setType($type);
        $post->setContent($content);
        $post->setSport($request->request->get('sport') ?: 'football');
        $post->setCreatedAt(new \DateTime());
        $post->setAnalysisTitle(
            $request->request->get('analysisTitle') ?: null
        );
        $post->setVipMatch(
            $request->request->get('vipMatch') ?: null
        );
        $post->setVipPrediction(
            $request->request->get('vipPrediction') ?: null
        );
        $post->setVipOdds(
            $request->request->get('vipOdds') ?: null
        );
        $post->setVipStake(
            $request->request->get('vipStake') ?: null
        );
        $vipConfidence = $request->request->get('vipConfidence');
        $post->setVipConfidence(
            $vipConfidence !== null && $vipConfidence !== ''
                ? (int) $vipConfidence
                : null
        );

        $entityManager->persist($post);
        $entityManager->flush();

        return new Response('Post créé avec l\'ID : ' . $post->getId());
    }

    #[Route('/community/post/{id}/like', name: 'app_community_post_like', methods: ['POST'])]
    public function like(
        CommunityPost $post,
        #[CurrentUser] ?\App\Entity\User $user,
        EntityManagerInterface $entityManager
    ): Response {

        // Vérifier que l'utilisateur est connecté
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Vous devez être connecté pour aimer une publication.'
            ], 401);
        }

        $likeRepository = $entityManager
            ->getRepository(\App\Entity\CommunityLike::class);

        // Vérifier si l'utilisateur connecté a déjà aimé ce post
        $like = $likeRepository->findOneBy([
            'user' => $user,
            'communityPost' => $post,
        ]);

        if ($like) {

            // Déjà liké → on retire le like
            $entityManager->remove($like);
            $liked = false;

        } else {

            // Pas encore liké → on ajoute le like
            $like = new \App\Entity\CommunityLike();

            $like->setUser($user);
            $like->setCommunityPost($post);
            $like->setCreatedAt(new \DateTime());

            $entityManager->persist($like);

            $liked = true;
        }

        $entityManager->flush();

        // Nouveau nombre total de likes
        $likeCount = $likeRepository->count([
            'communityPost' => $post,
        ]);

        return $this->json([
            'success' => true,
            'liked' => $liked,
            'count' => $likeCount,
        ]);
    }

    #[Route('/community/post/{id}/comment', name: 'app_community_post_comment', methods: ['POST']
    )]
    public function comment(
        CommunityPost $post,
        Request $request,
        EntityManagerInterface $entityManager,
        #[CurrentUser] ?\App\Entity\User $user
    ): Response {

        // Vérifier que l'utilisateur est connecté
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Vous devez être connecté pour commenter.'
            ], 401);
        }

        // Récupérer le contenu
        $content = trim(
            (string) $request->request->get('content')
        );

        // Vérifier que le commentaire n'est pas vide
        if ($content === '') {
            return $this->json([
                'success' => false,
                'message' => 'Le commentaire ne peut pas être vide.'
            ], 400);
        }

        // Création du commentaire
        $comment = new \App\Entity\CommunityComment();

        $comment->setUser($user);
        $comment->setCommunityPost($post);
        $comment->setContent($content);
        $comment->setCreatedAt(new \DateTime());

        // Sauvegarde BDD
        $entityManager->persist($comment);
        $entityManager->flush();

        return $this->json([
            'success' => true,

            'comment' => [
                'id' => $comment->getId(),
                'content' => $comment->getContent(),
                'createdAt' => $comment->getCreatedAt()->format('d/m/Y H:i'),
                'nickname' => $user->getNickname(),
                'photo' => $user->getPhoto(),
            ]
        ]);
    }


}